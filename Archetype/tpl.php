<?php

class template Extends PHPTAL {
    function render($echo = false) {
        // render view
		try {
			$result = $this->execute();
		} catch (Exception $e) {
			die($e->getMessage());
		}

        if ($echo) {
            echo $result;
        } else {
            return $result;
        }
    }

    function data($_data) {
        foreach($_data as $key => $val){
            $this->$key = $val;
        }
    }
}