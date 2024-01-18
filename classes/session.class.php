<?php

class Session {
    public function setUserdata($data, $value = null) {
		if (is_array($data)) {
			foreach ($data as $key => &$value) {
				$_SESSION[$key] = $value;
			}

			return;
		}

		$_SESSION[$data] = $value;
	}

	public function unsetUserdata($key) {
		if (is_array($key)) {
			foreach ($key as $k) {
				unset($_SESSION[$k]);
			}

			return;
		}

		unset($_SESSION[$key]);
	}
}
