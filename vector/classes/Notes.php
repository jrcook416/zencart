<?php
if (!defined ('IS_ADMIN_FLAG')) {
    exit ('Illegal Access');
}

class Notes extends base {
  
    public function __construct() {
    }

    public static function validate_table($table) { 
       if ($table == "products") {
          return true; 
       } else if ($table == "customers") {
          return true; 
       } else if ($table == "orders") {
          return true; 
       }
       die("bad table"); 
    }

    public static function validate_table_for_form($table) { 
       if ($table == "orders") {
          return true; 
       }
       die("bad table"); 
    }

    public function createInputField($id, $table) {
       Notes::validate_table($table); 
       $label = NOTES_LABEL; 
       $entry = $this->getNote($id, $table);
       if (empty($entry)) { 
          $entry = array('data' => '', 'note_id'=> ''); 
       }
       $note = '<div>' . zen_draw_textarea_field('note', 'soft', '100', '5', $entry['data'], 'id="note-input" class="form-control"') .  '</div>';
        $note .= zen_draw_hidden_field('id', $id, 'id="id"');
        $note .= zen_draw_hidden_field('note_id', $entry['note_id'], 'id="note_id"');

        if ($table == "customers") { 
          $note_inputs = array(
                'label' => $label, 
                'input' => $note 
            );
        } else {  
          $note_inputs = array(
                'label' => array(
                    'text' => $label, 
                    'field_name' => 'note',
                ),
                'input' => $note 
            );
        }
        return $note_inputs; 
    }

    public function createInputForm($id, $table) {
       Notes::validate_table_for_form($table); 
       $label = NOTES_LABEL; 
       $entry = $this->getNote($id, $table);
       if (empty($entry)) { 
          $entry = array('data' => '', 'note_id'=> ''); 
       }

       $note_inputs = '<br>
        <div class="row noprint">
         <div class="formArea">' . 
         zen_draw_form('notesUpdate', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=update_note', 'post', 'class="form-horizontal"', true) . '
            <div class="form-group">
               <label for="note" class="col-sm-3 control-label">' . $label . '</label>                  
                 <div class="col-sm-9">' . 
                 zen_draw_textarea_field('note', 'soft', '100', '5', $entry['data'], 'id="note-input" class="form-control"') . 
                 zen_draw_hidden_field('camefrom', 'orderEdit') . 
                 zen_draw_hidden_field('id', $id, 'id="id"') . 
                 zen_draw_hidden_field('note_id', $entry['note_id'], 'id="note_id"') . ' 
                 </div>
            </div>
            <div class="form-group">
                <div class="col-sm-9 col-sm-offset-3">
                      <button type="submit" class="btn btn-info">Update</button>
                </div>
            </div>
          </form>          
        </div>
       <div>'; 

       return $note_inputs; 
    }

    public function createDisplayField($id, $table) {
       Notes::validate_table_for_form($table); 
       $label = NOTES_LABEL; 
       $entry = $this->getNote($id, $table);
       if (empty($entry)) { 
          $entry = array('data' => '', 'note_id'=> ''); 
       }

       $note_inputs = '<b>' . $label . '</b>: ' . $entry['data']; 
       return $note_inputs; 
    }


    public function createPlaceholder($table) {
       Notes::validate_table($table); 
       $label = NOTES_LABEL; 
       if ($table == "products") {
          $message = NOTES_CREATE_PRODUCT_FIRST; 
       } else { 
          $message = NOTES_CREATE_CUSTOMER_FIRST; 
       }
       $note_inputs = array(
                'label' => array(
                    'text' => $label,
                    'field_name' => 'note',
                ),
                'input' => $message . zen_draw_hidden_field('note', '')
        );
        return $note_inputs;
    }

    public function getNote($id, $table) {
       global $db; 
       Notes::validate_table($table); 
       $query = $db->Execute("SELECT * FROM " . TABLE_NOTES . " WHERE id = " . (int)$id . " AND table_name='" . $table . "'"); 
       if ($query->EOF) {
          return null; 
       }
       return $query->fields; 
    }

    public function removeNote($id, $table) {
       global $db; 
       Notes::validate_table($table); 
       $db->Execute("DELETE FROM " . TABLE_NOTES . " WHERE id = " . (int)$id . " AND table_name='" . $table . "'"); 
    }

    public function updateNote($id, $table) {
       global $db; 
       Notes::validate_table($table); 
       $data = zen_db_prepare_input($_POST['note']); 
       $sql_data_array = array( 'data' => $data);

       if (empty($_POST['note_id'])) {
          $sql_data_array['id'] = $_POST['id']; 
          $sql_data_array['table_name'] = $table; 
          zen_db_perform(TABLE_NOTES, $sql_data_array);
       } else {
          zen_db_perform(TABLE_NOTES, $sql_data_array, 'update', 'note_id=' . (int)$_POST['note_id']);
       }
    }
} 
