<?php

//
//	Класс BillManager предназначен для работы с API BILLmanager v4.
//

class BillManager
{
	protected $auth_token;
	protected $web_url = "https://web.ru/manager/billmgr"; // Ваш сайт

	//
	// Авторизация пользователя
	//
	// Параметры:
	// 		$username - логин пользователя;
	// 		$password - пароль пользователя.
	// Ответ:
	// 		$status (bool) статус запроса;
	// 		$auth (int) токен авторизации;
	// 		$message (str)  сообщение об успехе или ошибке.

    function auth($username, $password){
		$request_auth = curl_init("{$this->web_url}?out=json&func=auth&username={$username}&password={$password}");
		curl_setopt($request_auth, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request_auth, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request_auth, CURLOPT_HEADER, false);
		$auth_JSON = curl_exec($request_auth);
		curl_close($request_auth);
		$auth = json_decode($auth_JSON, true);

		if (isset($auth['authfail'])) {
			return ["status" => false, "message" => "Ошибка авторизации!"];
		}
		if (!isset($auth['auth'])) {
			return ["status" => false, "message" => "Ошибка получения ключа авторизации!"];
		}

		$auth_token = $auth['auth'];
		$this->auth_token = $auth_token;

		return ["status" => true, "auth" => $auth_token, "message" => "Success"];
	}

	//
	// Авторизация пользователя с использованием токена
	// 
	// Параметры:
	// 		$token - токен авторизации.
	// Ответ:
	// 		$status (bool) статус запроса;
	// 		$auth (int) токен авторизации.

	function auth_token($token){
		$this->auth_token = $token;
		return ["status" => true, "auth" => $this->auth_token];
	}


	// Получение таблицы
	// Параметры:
	// 		$name_table - название таблицы;
	// 		$filter (Array) - фильтр выбора записей с нужными параметрами.
	// Ответ:
	// 		$status (bool) - статус запроса;
	// 		$elem (array) - записи таблицы;
	// 		$message (str)  - сообщение об успехе или ошибке.


	function get_table($name_table, $filter = array()){
		if (!isset($this->auth_token)) {
			return ["status" => false, "message" => "Ключ авторизации не найден!"];
		}

		$request_table = curl_init("{$this->web_url}?auth={$this->auth_token}&out=json&func={$name_table}");
		curl_setopt($request_table, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request_table, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request_table, CURLOPT_HEADER, false);
		$table_JSON = curl_exec($request_table);
		curl_close($request_table);
		$table_elements = json_decode($table_JSON, true);

		if (!isset($table_elements['elem'])) {
			return ["status" => false, "message" => "Не удалось получить список \"{$name_table}\"!"];
		}

		if (isset($filter["search"])) {
			foreach ($filter["search"] as $search_key => $search_value) {
				foreach ($table_elements['elem'] as $index => &$elem_value) {
					if (isset($elem_value[$search_key]) && $elem_value[$search_key] != $search_value) {
						unset($table_elements['elem'][$index]);
					}
				}
			}
		}

		$table_elements["elem"] = array_reverse($table_elements["elem"]);

		return ["status" => true, "elem" => $table_elements["elem"], "message" => "Success"];
	}

	//
	// Получение информации об авторизованном пользователе
	//
	// Ответ:
	// 		$status (bool) - статус запроса;
	// 		$elem (array) - информация о пользователе;
	// 		$message (str)  - сообщение об успехе или ошибке.

	function get_user_info(){
		if (!isset($this->auth_token)) {
			return ["status" => false, "message" => "Ключ авторизации не найден!"];
		}

		$request_user = curl_init("{$this->web_url}?auth={$this->auth_token}&out=json&func=user");
		curl_setopt($request_user, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request_user, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request_user, CURLOPT_HEADER, false);
		$user_JSON = curl_exec($request_user);
		curl_close($request_user);
		$user_all = json_decode($user_JSON, true);

		if (!isset($user_all['elem'])) {
			return ["status" => false, "message" => "Не удалось получить информацию о пользователе!"];
		}

		return ["status" => true, "elem" => $user_all["elem"], "message" => "Success"];
	}


	//
	// Удаление элемента из таблицы
	//
	// Параметры:
	// 		$elem - id элемент для удаления;
	// 		$name_table - название таблицы.
	// Ответ:
	// 		$status (bool) - статус запроса;
	// 		$message (str) - сообщение об успехе или ошибке.

	function remove_table_element($elem, $name_table){
		if (!isset($this->auth_token)) {
			return ["status" => false, "message" => "Ключ авторизации не найден!"];
		}

		$request_remove = curl_init("{$this->web_url}?out=json&auth={$this->auth_token}&func={$name_table}.delete&elid={$elem}");
		curl_setopt($request_remove, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request_remove, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request_remove, CURLOPT_HEADER, false);
		$request_remove_JSON = curl_exec($request_remove);
		curl_close($request_remove);
		$remove = json_decode($request_remove_JSON, true);

		if (!isset($remove['ok'])) {
			return ["status" => false, "message" => $remove["error"]["msg"]];
		}

		return ["status" => true, "message" => "Success"];
	} 

	//
	// Получение данных из таблицы по идентификатору
	//
	// Параметры:
	// 		$table - название таблицы;
	// 		$elid - идентификатор элемента;
	// 		$desired_item (Array) - массив полей таблицы которые необходимо получить.
	// Ответ:
	// 		$status (bool) - статус запроса;
	// 		$elem (array) - записи из таблицы;

	function get_table_element($table, $elid, $desired_item = array()){
		if (!isset($this->auth_token)) {
			return ["status" => false, "message" => "Ключ авторизации не найден!"];
		}

		$request_element = curl_init("{$this->web_url}?auth={$this->auth_token}&out=json&func={$table}.edit&elid={$elid}");
		curl_setopt($request_element, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request_element, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request_element, CURLOPT_HEADER, false);
		$request_element_JSON = curl_exec($request_element);
		curl_close($request_element);
		$element_table = json_decode($request_element_JSON, true);
		if (isset($element_table["error"])) {
			return ["status" => false, "message" => $element_table["error"]["msg"]];
		}

		if (count($desired_item) > 0) {
			$element_table = array_intersect_key($element_table, array_flip($desired_item));
		}

		return ["status" => true, "elem" => $element_table];
	}

	//
	// Подтверждение платежа
	//
	// Параметры:
	// 		$elid - идентификатор платежа из таблицы “credit”.
	// Ответ:
	// 		$status (bool) статус запроса;
	// 		$elem (array) ответ на запрос подтверждения платежа;
	// 		$message (str)  сообщение об успехе или ошибке.

	function check_pay($elid){
		if (!isset($this->auth_token)) {
			return ["status" => false, "message" => "Ключ авторизации не найден!"];
		}

		$desired_item = ["num", "cdate", "pnum", "pdocdate", "pdate", "sender", "amount", "usedamount", "currency", "nativeamount", "pstate", "type", "info",  "note"];
		$old_element_data = $this->get_table_element("credit", $elid, $desired_item);

		if (!$old_element_data["status"]) {
			return ["status" => false, "message" => $old_element_data["message"]];
		}

		$old_element_data = $old_element_data['elem'];

		$edit_element_data = ["elid" => $elid, "auth" => $this->auth_token, "func" => "credit.edit", "pstate" => "payd", "restrictrefund" => "on", "out" => "json", "sok" => "ok"];
		$new_element_data = array_replace($old_element_data, $edit_element_data);

		$request_url = http_build_query($new_element_data, '', '&');
		$request_url = "{$this->web_url}?".$request_url;

		$request_element = curl_init($request_url);
		curl_setopt($request_element, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request_element, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($request_element, CURLOPT_HEADER, false);
		$request_element_JSON = curl_exec($request_element);
		curl_close($request_element);
		$element_table = json_decode($request_element_JSON, true);

		if (isset($element_table["error"])) {
			return ["status" => false, "message" => $element_table["error"]["msg"]];
		}

		return ["status" => true, "elem" => $element_table, "message" => "Success"];
	} 

	//
	// Подтверждение списка платежей
	//
	// Параметры:
	// 		$el_list - массив с идентификаторами платежей из таблицы “credit”.
	// Ответ:
	// 		$status (bool) - статус запроса;
	// 		$elem (array) - ответы на запросы подтверждения платежей;
	// 		$message (str) - сообщение об успехе или ошибке.

	function check_pay_list($el_list){
		if (!isset($this->auth_token)) {
			return ["status" => false, "message" => "Ключ авторизации не найден!"];
		}
		$all_request = array();
		foreach ($el_list as $index => $value) {
			$desired_item = ["num", "cdate", "pnum", "pdocdate", "pdate", "sender", "amount", "usedamount", "currency", "nativeamount", "pstate", "type", "info",  "note"];
			$old_element_data = $this->get_table_element("credit", $value, $desired_item);

			if (!$old_element_data["status"]) {
				return ["status" => false, "message" => $old_element_data["message"]];
			}

			$old_element_data = $old_element_data['elem'];

			$edit_element_data = ["elid" => $value, "auth" => $this->auth_token, "func" => "credit.edit", "pstate" => "payd", "restrictrefund" => "on", "out" => "json", "sok" => "ok"];
			$new_element_data = array_replace($old_element_data, $edit_element_data);

			$request_url = http_build_query($new_element_data, '', '&');
			$request_url = "{$this->web_url}?{$request_url}";
			
			$request_element = curl_init($request_url);
			curl_setopt($request_element, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($request_element, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($request_element, CURLOPT_HEADER, false);
			$request_element_JSON = curl_exec($request_element);
			curl_close($request_element);
			$element_table = json_decode($request_element_JSON, true);

			array_push($all_request, $element_table);
		}

		return ["status" => true, "elem" => $all_request, "message" => "Success"];
	} 

}
