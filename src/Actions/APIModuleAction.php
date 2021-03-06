<?php

namespace InfamousQ\LManager\Actions;

use Hybridauth\Exception\NotImplementedException;
use InfamousQ\LManager\Models\APIModuleMapper;
use InfamousQ\LManager\Models\APIPlatesMapper;
use \Slim\Http\Response;
use \Slim\Http\Request;
use \Slim\Http\StatusCode;

class APIModuleAction {

	use readsUserDataFromToken;

	/** @var \InfamousQ\LManager\Services\ModuleService $module_service */
	protected $module_service;
	/** @var \InfamousQ\LManager\Services\UserServiceInterface $user_service */
	protected $user_service;

	public function __construct(\Slim\Container $container) {
		$this->module_service = $container->get('module');
		$this->user_service = $container->get('user');
	}

	public function fetchList(Request $request, Response $response) {
		$public_modules = $this->module_service->getPublicModules();
		$result = [];
		foreach ($public_modules as $public_module) {
			$result[] = APIModuleMapper::getSummaryJSON($public_module);
		}
		return $response->withJson($result, StatusCode::HTTP_OK);
	}

	public function insert(Request $request, Response $response, array $args = []) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}
		$json_fields = $request->getParsedBody();
		$allowed_module_field_keys = ['name'];
		$allowed_json_fields = array_intersect_key($json_fields, array_flip($allowed_module_field_keys));
		$new_module = $this->module_service->createModule($allowed_json_fields['name'], $current_user->id);
		if (null === $new_module) {
			// TODO: send error
			error_log('module creation fail!');
		}
		return $response->withJson(APIModuleMapper::getJSON($new_module), StatusCode::HTTP_OK);
	}

	public function fetchSingle(Request $request, Response $response, array $args = array()) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}
		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);
		if ($target_module === null) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}
		return $response->withJson(APIModuleMapper::getJSON($target_module), StatusCode::HTTP_OK);
	}

	public function editSingle(Request $request, Response $response, array $args = array()) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);

		if ($target_module->user->id !== $current_user->id) {
			return $response->withJson(['error' => ['message' => "Can't edit someone else's module"]], StatusCode::HTTP_UNAUTHORIZED);
		}

		$json_fields = $request->getParsedBody();
		$allowed_module_field_keys = ['name', 'w', 'h'];
		$allowed_json_fields = array_intersect_key($json_fields, array_flip($allowed_module_field_keys));

		foreach ($allowed_json_fields as $field => $value) {
			$target_module->$field = $value;
		}
		if (!$this->module_service->saveModule($target_module)) {
			return $response->withJson(['error' => ['message' => 'Module saving failed']], StatusCode::HTTP_BAD_REQUEST);
		}
		return $response->withJson(APIModuleMapper::getJSON($target_module), StatusCode::HTTP_OK);
	}

	public function deleteSingle(Request $request, Response $response, array $args = array()) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);

		if (null === $target_module) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		if ($target_module->user->id !== $current_user->id) {
			return $response->withJson(['error' => ['message' => 'Not owner']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!$this->module_service->deleteModuleById($target_module->id)) {
			return $response->withJson(['error' => ['message' => 'Module deletion failed']], StatusCode::HTTP_BAD_REQUEST);
		}
		return $response->withStatus( StatusCode::HTTP_OK);
	}

	public function fetchPlateList(Request $request, Response $response, array $args = array()) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);
		if (null === $target_module) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		return $response->withJson(APIPlatesMapper::getModulePlatesJSON($target_module),StatusCode::HTTP_OK);
	}

	public function insertPlate(Request $request, Response $response, array $args = array()) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);
		if (null === $target_module) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$json_fields = $request->getParsedBody();
		if (empty($json_fields)) {
			return $response->withJson(['error' => ['message' => 'Invalid content']], StatusCode::HTTP_BAD_REQUEST);
		}
		$allowed_plate_field_keys = ['x', 'y', 'z', 'h', 'w', 'color_id'];
		$allowed_json_fields = array_intersect_key($json_fields, array_flip($allowed_plate_field_keys));
		$allowed_json_fields['module'] = $target_module->id;
		$target_plate = $this->module_service->createPlate(
			(int) $allowed_json_fields['x'],
			(int) $allowed_json_fields['y'],
			(int) $allowed_json_fields['z'],
			(int) $allowed_json_fields['h'],
			(int) $allowed_json_fields['w'],
			(int) $allowed_json_fields['color_id'],
			(int) $allowed_json_fields['module']
			);
		if (null == $target_plate) {
			return $response->withJson(['error' => ['message' => 'Plate saving failed']], StatusCode::HTTP_BAD_REQUEST);
		}
		return $response->withJson(APIPlatesMapper::getJSON($target_plate), StatusCode::HTTP_OK);
	}

	public function editPlate(Request $request, Response $response, array $args = []) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);
		if (null === $target_module) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		if (!array_key_exists('plate_id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_plate_id = (int) $args['plate_id'];
		$target_plate = $this->module_service->getPlateById($target_plate_id);
		if (null === $target_plate) {
			return $response->withJson(['error' => ['message' => 'Plate not found']], StatusCode::HTTP_NOT_FOUND);
		}
		if ($target_plate->module->id !== $target_module_id) {
			return $response->withJson(['error' => ['message' => 'Plate not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$json_fields = $request->getParsedBody();
		if (empty($json_fields)) {
			return $response->withJson(['error' => ['message' => 'Invalid content']], StatusCode::HTTP_BAD_REQUEST);
		}
		$allowed_plate_field_keys = ['x', 'y', 'z', 'h', 'w', 'color_id'];
		$allowed_json_fields = array_intersect_key($json_fields, array_flip($allowed_plate_field_keys));
		foreach ($allowed_json_fields as $field => $value) {
			$target_plate->$field = $value;
		}
		if (!$this->module_service->savePlate($target_plate)) {
			return $response->withJson(['error' => ['message' => 'Plate saving failed']], StatusCode::HTTP_BAD_REQUEST);
		}
		// Force refresh to update color relationship
		$target_plate = $this->module_service->getPlateById($target_plate_id);
		return $response->withJson(APIPlatesMapper::getJSON($target_plate), StatusCode::HTTP_OK);
	}

	public function deletePlate(Request $request, Response $response, array $args = []) {

		try {
			$this->getUserDataFromToken($request);
		} catch (\InvalidArgumentException $e) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		$current_user = $this->user_service->getUserById($this->token_user_data->id);
		if (null === $current_user) {
			return $response->withJson(['error' => ['message' => 'Invalid token']], StatusCode::HTTP_UNAUTHORIZED);
		}

		if (!array_key_exists('id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_module_id = (int) $args['id'];
		$target_module = $this->module_service->getModuleById($target_module_id);
		if (null === $target_module) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		if (!array_key_exists('plate_id', $args)) {
			return $response->withJson(['error' => ['message' => 'Module not found']], StatusCode::HTTP_NOT_FOUND);
		}

		$target_plate_id = (int) $args['plate_id'];
		$target_plate = $this->module_service->getPlateById($target_plate_id);
		if (null === $target_plate) {
			return $response->withJson(['error' => ['message' => 'Plate not found']], StatusCode::HTTP_NOT_FOUND);
		}
		if ($target_plate->module->id !== $target_module_id) {
			return $response->withJson(['error' => ['message' => 'Plate not found']], StatusCode::HTTP_NOT_FOUND);
		}

		if (!$this->module_service->deletePlateById($target_plate->id)) {
			return $response->withJson(['error' => ['message' => 'Plate deletion failed']], StatusCode::HTTP_BAD_REQUEST);
		}
		return $response->withStatus( StatusCode::HTTP_OK);
	}
}
