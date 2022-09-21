#!/bin/bash
git diff --name-only > dotWebsiteDiffs.txt v157d-dev..v157d-clean -- . ':!*.gif' ':!*.jpg' ':*.jpeg'
