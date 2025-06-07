<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
 
class block_recommendation_edit_form extends block_edit_form {

    // Perform some extra moodle validation
    function validation($data, $files) {
        return $this->validation_high_security($data, $files);    
    }
 
    protected function specific_definition($mform) {
 		global $CFG,$PAGE,$USER;
        $context = context_system::instance();
        $roles = get_user_roles($context, $USER->id);
        /*foreach ($roles as $role) {
            if ($role->shortname == 'manager') {
              
            }
        }*/

        
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_recommendation'));
        $mform->setType('config_title', PARAM_TEXT);

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean'=>true, 'context'=>$this->block->context);
        $mform->addElement('editor', 'config_text', get_string('configcontent', 'block_recommendation'), null, $editoroptions);
        $mform->addRule('config_text', null, 'required', null, 'client');
        $mform->setType('config_text', PARAM_RAW); // XSS is prevented when printing the block contents and serving files

        //Settings fields
        
        $mform->addElement('advcheckbox', 'config_tags', get_string('config_tags', 'block_recommendation'));
        $mform->addElement('advcheckbox', 'config_map', get_string('config_map', 'block_recommendation'));
       // $mform->addElement('textarea', 'config_querysql', get_string('querysql', 'block_recommendation'),
         //   'rows="10" cols="20"');
                
    }


    function validation_high_security($data, $files) {
        global $DB, $CFG, $db, $USER;
        
       /* if(isset($data['config_querysql']) && !empty($data['config_querysql'])){
            $errors = parent::validation($data, $files);

            $sql = $data['config_querysql'];
            $sql = trim($sql);

            // Simple test to avoid evil stuff in the SQL.
            if (preg_match('/\b(ALTER|CREATE|DELETE|DROP|GRANT|INSERT|INTO|TRUNCATE|UPDATE|SET|VACUUM|REINDEX|DISCARD|LOCK)\b/i', $sql)) {
                $errors['config_querysql'] = get_string('notallowedwords', 'block_recommendation');

            // Do not allow any semicolons.
            } else if (strpos($sql, ';') !== false) {
                $errors['config_querysql'] = get_string('nosemicolon', 'report_customsql');

            // Make sure prefix is prefix_, not explicit.
            } else if (preg_match('/\b' . $CFG->prefix . '\w+/i', $sql)) {
                $errors['config_querysql'] = get_string('noexplicitprefix', 'block_recommendation');

            // Now try running the SQL, and ensure it runs without errors.
            } else {
                
                $rs = $DB->get_records_sql($sql, null, null,null);
                if (!$rs) {

                    $errors['config_querysql'] = get_string('norowsreturned', 'block_recommendation', $db->ErrorMsg());
                } else if (!empty($data['singlerow'])) {
                    if (rs_EOF($rs)) {
                        $errors['config_querysql'] = get_string('norowsreturned', 'block_recommendation');
                    }
                }
            }

            return $errors;
        }*/
    }

}   