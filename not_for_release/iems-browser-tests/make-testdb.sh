#!/bin/bash
# make-testdb.sh - makes a scrubbed copy of one
# store's 2.2 database for local testing.
#
#   bash make-testdb.sh          (IEMS store)
#   bash make-testdb.sh leo      (another store)
#
# Runs on the DEV server only. It never connects
# to prod. In order, it:
#  1. finds the newest prod backup already pulled
#     to ~/_offsite_backups;
#  2. checks the backup holds iems_<store>_v222
#     and stops if it doesn't;
#  3. loads it into the scratch database
#     (restoretest; its old contents are wiped);
#  4. checks it is a Zen Cart 2.2 database;
#  5. replaces customer details (names, emails,
#     phones, street addresses, IPs, card fields,
#     order comments) with fake ones; keeps
#     products, stock, agencies, units, cities,
#     zip codes, order statuses and settings;
#  6. turns off store email and blanks payment
#     and SMTP passwords;
#  7. sets one test admin and one test customer
#     with known passwords (printed at the end);
#  8. writes ~/_testdb/<store>-testdb-<date>.sql.gz
#     and checks no real email address is left.
# Ends with RESULT: GOOD or RESULT: PROBLEM.
set -uo pipefail
umask 077

STORE="${1:-iems}"
case " iems external ifd leo wayne " in
  *" $STORE "*) ;;
  *) echo "Unknown store: $STORE"; exit 1 ;;
esac
DBNAME="iems_${STORE}_v222"
ADMIN_PW="iems-admin-2026"
CUST_PW="iems-test-2026"
CUST_EMAIL="jeremiah@jeremiahcook.net"
ARCH="$HOME/_offsite_backups"
OUTDIR="$HOME/_testdb"
TESTCNF="$HOME/.backup/restoretest.cnf"

LOADED=0
stop() {
  echo
  echo "STOPPED: $*"
  # don't leave real data in the scratch database
  [ "$LOADED" = 1 ] && { LOADED=0; wipe; }
  echo "RESULT: PROBLEM - nothing was written."
  exit 1
}
ok() { echo "  ok  $*"; }

echo "IEMS test database - store: $STORE"
echo

# never on prod
[ -f "$HOME/_db_backups/backup-v3.sh" ] && \
  stop "this looks like the prod server." \
       "Run this on dev only."
[ -d "$ARCH" ] || stop "no $ARCH folder (not dev?)"

# PHP for password hashes (dev: /usr/local/bin/php)
PHP=""
for p in /usr/local/bin/php \
  /opt/cpanel/ea-php83/root/usr/bin/php \
  /opt/cpanel/ea-php82/root/usr/bin/php php; do
  command -v "$p" >/dev/null 2>&1 || continue
  v=$("$p" -r 'echo PHP_VERSION_ID;' 2>/dev/null)
  if [ "${v:-0}" -ge 80200 ] 2>/dev/null; then
    PHP="$p"; break
  fi
done
[ -n "$PHP" ] || stop "no PHP 8.2+ found"
command -v perl >/dev/null || stop "perl missing"

# scratch database settings (same as rebuild.sh)
[ -r "$TESTCNF" ] || stop "missing $TESTCNF"
cnfval() {
  sed -nE "s/^ *$1 *= *\"?([^\"]*)\"? *$/\1/p" \
    "$TESTCNF" 2>/dev/null | head -n 1
}
TU=$(cnfval user); TU=${TU:-$(whoami)_restoretest}
TD=$(cnfval database); TD=${TD:-$TU}
case "$TD" in *restoretest) ;;
  *) stop "scratch database name must end in" \
          "restoretest (it gets wiped)" ;; esac
q() {
  mysql --defaults-extra-file="$TESTCNF" -u "$TU" \
    -N -B -e "$1" "$TD"
}
# empty the scratch database: views, then tables
wipe() {
  local kind t
  for kind in "VIEW:VIEW" "BASE TABLE:TABLE"; do
    t=$(q "SET SESSION group_concat_max_len=1000000;
      SELECT GROUP_CONCAT(CONCAT('\`',table_name,
      '\`')) FROM information_schema.tables WHERE
      table_schema=DATABASE() AND
      table_type='${kind%%:*}'") || return 1
    if [ -n "$t" ] && [ "$t" != "NULL" ]; then
      q "SET FOREIGN_KEY_CHECKS=0;
         DROP ${kind##*:} $t" || return 1
    fi
  done
}
e=$(q "SELECT 1" 2>&1 >/dev/null) || \
  stop "cannot use $TD: $e"

# 1-2. newest backup that holds this store
echo "Step 1: newest backup"
ARCHIVE=$(ls "$ARCH"/archive/iems-backup-*.tar.gz \
  "$ARCH"/daily/iems-backup-*.tar.gz 2>/dev/null \
  | awk -F/ '{print $NF"\t"$0}' | sort -r \
  | head -n 1 | cut -f2)
[ -n "$ARCHIVE" ] || stop "no backups in $ARCH"
ok "$(basename "$ARCHIVE")"
TMP=$(mktemp -d "$OUTDIR.tmp.XXXXXX") || \
  stop "could not make a temp folder"
trap 'rm -rf "$TMP"' EXIT
DUMP="_restore/$DBNAME.sql.gz"
tar -xzf "$ARCHIVE" -C "$TMP" "$DUMP" \
  2>/dev/null || stop "this backup has no" \
  "$DBNAME dump. Is pull-backup.sh pulling the" \
  "2.2 databases?"
ok "holds $DBNAME"

# 3. load into the scratch database
echo
echo "Step 2: load into $TD (wiped first)"
wipe || stop "could not wipe $TD"
LOADED=1
gunzip -c "$TMP/$DUMP" \
  | sed -E '/^\/\*!50/ s/DEFINER=`[^`]*`@`[^`]*`//g' \
  | mysql --defaults-extra-file="$TESTCNF" --force \
      -u "$TU" "$TD" 2>"$TMP/load.err"
nerr=$(grep -c '^ERROR' "$TMP/load.err")
# views that read another database may fail; they
# are report helpers, so they are dropped
bad_v=$(q "SELECT table_name FROM
  information_schema.views WHERE
  table_schema=DATABASE()" 2>/dev/null)
for v in $bad_v; do
  q "SELECT 1 FROM \`$v\` LIMIT 1" \
    >/dev/null 2>&1 || {
    q "DROP VIEW IF EXISTS \`$v\`"
    echo "  note: dropped view $v (reads another db)"
  }
done
nv=$(grep '^ERROR' "$TMP/load.err" \
     | grep -ciE 'view|definer|command denied')
[ "$((nerr-nv))" -le 0 ] || stop "load errors:" \
  "$(grep '^ERROR' "$TMP/load.err" | grep -viE \
     'view|definer|command denied' | head -n 1 \
     | cut -c1-100)"
ok "loaded"

# 4. Zen Cart 2.2?
P=$(q "SELECT LEFT(table_name, LENGTH(table_name)
  - LENGTH('project_version')) FROM
  information_schema.tables WHERE
  table_schema=DATABASE() AND
  table_name LIKE '%project\_version'" | head -n 1)
for t in customers orders admin configuration; do
  q "SELECT 1 FROM \`$P$t\` LIMIT 1" >/dev/null \
    2>&1 || stop "table $P$t missing"
done
ver=$(q "SELECT CONCAT(project_version_major,'.',
  project_version_minor) FROM ${P}project_version
  WHERE project_version_key='Zen-Cart Main'")
case "$ver" in 2.2*) ;;
  *) stop "database is Zen Cart '$ver', not 2.2" ;;
esac
ok "Zen Cart $ver, table prefix '${P}'"

# 5-7. scrub
echo
echo "Step 3: replace customer details"
hash() {
  "$PHP" -r 'echo password_hash($argv[1],
    PASSWORD_DEFAULT);' "$1"
}
AH=$(hash "$ADMIN_PW"); CH=$(hash "$CUST_PW")
[ "${#AH}" -ge 50 ] && [ "${#CH}" -ge 50 ] || \
  stop "could not make password hashes"
# a test admin = lowest superuser; test customer =
# whoever placed the newest order
TA=$(q "SELECT MIN(admin_id) FROM ${P}admin
  WHERE admin_profile=1")
TC=$(q "SELECT customers_id FROM ${P}orders
  WHERE customers_id IN (SELECT customers_id
  FROM ${P}customers) ORDER BY orders_id DESC
  LIMIT 1")
[ -n "$TA" ] && [ "$TA" != "NULL" ] || \
  stop "no superuser admin found"
[ -n "$TC" ] && [ "$TC" != "NULL" ] || \
  stop "no customer with an order found"

# tables emptied if present (logs, sessions,
# tokens, email copies, payment records)
EMPTY="admin_activity_log sessions whos_online"
EMPTY="$EMPTY email_archive db_cache"
EMPTY="$EMPTY customers_auth_tokens"
EMPTY="$EMPTY customer_password_reset_tokens"
EMPTY="$EMPTY admin_expired_tokens paypal"
EMPTY="$EMPTY paypal_session paypal_webhooks"
EMPTY="$EMPTY paypal_payment_status_history"
EMPTY="$EMPTY authorizenet files_uploaded"
EMPTY="$EMPTY customers_basket"
EMPTY="$EMPTY customers_basket_attributes"
TRUNC=""
for t in $EMPTY; do
  n=$(q "SELECT COUNT(*) FROM
    information_schema.tables WHERE
    table_schema=DATABASE() AND
    table_name='$P$t'")
  [ "$n" = "1" ] && TRUNC+="TRUNCATE ${P}$t;"
done

SQL=$(cat <<SQLEOF
$TRUNC
UPDATE {P}customers SET
  customers_firstname='Test',
  customers_lastname=CONCAT('Customer',
    customers_id),
  customers_email_address=CONCAT('cust',
    customers_id,'@example.test'),
  customers_nick='', customers_telephone=
  '555-0100', customers_fax='',
  customers_password='!disabled',
  customers_secret='', registration_ip='',
  last_login_ip='';
UPDATE {P}customers SET
  customers_email_address='$CUST_EMAIL',
  customers_password='$CH',
  customers_authorization=0
  WHERE customers_id=$TC;
UPDATE {P}address_book SET
  entry_firstname='Test',
  entry_lastname=CONCAT('Customer',customers_id),
  entry_street_address=CONCAT(address_book_id,
    ' Test St');
UPDATE {P}orders SET
  customers_name=CONCAT('Test Customer',
    customers_id),
  customers_street_address='1 Test St',
  customers_telephone='555-0100',
  customers_email_address=CONCAT('cust',
    customers_id,'@example.test'),
  delivery_name=CONCAT('Test Customer',
    customers_id),
  delivery_street_address='1 Test St',
  billing_name=CONCAT('Test Customer',
    customers_id),
  billing_street_address='1 Test St',
  cc_owner='', cc_number='', cc_expires='',
  cc_cvv='', ip_address='';
UPDATE {P}orders SET
  customers_email_address='$CUST_EMAIL'
  WHERE customers_id=$TC;
UPDATE {P}orders_status_history
  SET comments='Test comment'
  WHERE comments IS NOT NULL AND comments<>'';
UPDATE {P}reviews SET customers_name=
  'Test Customer';
UPDATE {P}coupon_email_track SET
  emailed_to='test@example.test',
  sent_firstname='', sent_lastname='';
UPDATE {P}coupon_gv_queue SET ipaddr='';
UPDATE {P}coupon_redeem_track SET redeem_ip='';
UPDATE {P}admin SET
  admin_name=CONCAT('admin',admin_id),
  admin_email=CONCAT('admin',admin_id,
    '@example.test'),
  admin_pass='!disabled', prev_pass1='',
  prev_pass2='', prev_pass3='', reset_token='',
  last_login_ip='', last_failed_ip='',
  mfa=NULL;
UPDATE {P}admin SET admin_name='testadmin',
  admin_email='testadmin@example.test',
  admin_pass='$AH', pwd_last_change_date=NOW(),
  failed_logins=0, lockout_expires=0
  WHERE admin_id=$TA;
UPDATE {P}configuration SET configuration_value=
  'false' WHERE configuration_key='SEND_EMAILS';
UPDATE {P}configuration SET configuration_value=
  'False' WHERE configuration_key='MFA_ENABLED';
UPDATE {P}configuration SET configuration_value=
  'test@example.test' WHERE configuration_value
  LIKE '%@%' AND configuration_value
  NOT LIKE '%@iemssupply.net%';
UPDATE {P}configuration SET configuration_value=''
  WHERE configuration_key IN
  ('EMAIL_SMTPAUTH_PASSWORD',
   'EMAIL_SMTPAUTH_MAILBOX') OR
  (configuration_key LIKE 'MODULE\_PAYMENT\_%'
   AND configuration_key REGEXP
   'SECRET|CLIENT|PASSWORD|PWD|API|KEY|SIGNATURE|'
   'TOKEN|LOGIN|USER|MERCHANT|VENDOR|PARTNER'
   AND configuration_key NOT REGEXP
   'STATUS|SORT|ZONE|MODE|SERVER|DEBUG');
SQLEOF
)
SQL=${SQL//\{P\}/$P}
e=$(q "$SQL" 2>&1) || stop "scrub failed: ${e:0:150}"
ok "customers, addresses, orders, admins"
ok "logs, sessions, tokens, payment records emptied"
ok "store email off, payment/SMTP passwords blank"

# 8. dump, with a last sweep for any email address
# left in other tables (kept: iemssupply.net,
# example.test and the test customer's address)
echo
echo "Step 4: write the file"
mkdir -p "$OUTDIR"
export KEEP="$CUST_EMAIL"
OUT="$OUTDIR/$STORE-testdb-$(date +%Y%m%d-%H%M).sql.gz"
mysqldump --defaults-extra-file="$TESTCNF" \
  -u "$TU" --no-tablespaces --single-transaction \
  --skip-triggers "$TD" 2>"$TMP/dump.err" \
  | sed -E 's/DEFINER=`[^`]*`@`[^`]*`//g' \
  | perl -pe 's{([A-Za-z0-9._%+-]+)\@((?:[A-Za-z0-9-]+\.)+[A-Za-z]{2,})}{
      my ($u, $d) = ($1, $2);
      lc("$u\@$d") eq lc($ENV{KEEP}) ||
      $d =~ /(^|\.)(iemssupply\.net|example\.test)$/i
      ? "$u\@$d" : "x".(++$n)."\@example.test" }ge' \
  | gzip > "$OUT"
[ "${PIPESTATUS[0]}" -eq 0 ] || { rm -f "$OUT";
  stop "dump failed: $(head -c 150 "$TMP/dump.err")"; }
gunzip -c "$OUT" | tail -n 1 | grep -q \
  'Dump completed' || { rm -f "$OUT";
  stop "dump file is cut off"; }
left=$(gunzip -c "$OUT" | grep -oE \
  '[A-Za-z0-9._%+-]+@([A-Za-z0-9-]+\.)+[A-Za-z]{2,}' \
  | grep -viE '@(.*\.)?(iemssupply\.net|example\.test)$' \
  | grep -viFx "$CUST_EMAIL" \
  | sort -u | head -n 3)
[ -z "$left" ] || { rm -f "$OUT";
  stop "real email addresses still in file"; }
ok "no real email addresses in the file"
counts=$(q "SELECT (SELECT COUNT(*) FROM ${P}orders),
  (SELECT COUNT(*) FROM ${P}customers),
  (SELECT COUNT(*) FROM ${P}products)")
set -- $counts
ok "orders: $1  customers: $2  products: $3"
ok "size: $(du -h "$OUT" | cut -f1)"
# empty the scratch database so no copy lingers
LOADED=0
wipe

echo
echo "File:   $OUT"
echo "Admin:  testadmin / $ADMIN_PW"
echo "Store:  $CUST_EMAIL / $CUST_PW"
echo "Table prefix: '${P}'"
echo
echo "RESULT: GOOD"
