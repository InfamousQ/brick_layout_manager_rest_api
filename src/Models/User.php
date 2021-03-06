<?php

namespace InfamousQ\LManager\Models;

use Spot\EntityInterface;
use Spot\MapperInterface;

/**
 * Class User
 * @package InfamousQ\LManager\Models
 * @property-read int $id
 * @property string $name
 * @property string $email
 * @property-read \Spot\Entity\Collection $modules
 * @property-read \Spot\Entity\Collection $layouts
 */

class User extends \Spot\Entity {

	protected static $table = 'users';
	public static function fields() {
		return [
			'id'    => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
			'name'  => ['type' => 'string', 'required' => true],
			'email' => ['type' => 'string', 'required' => true],
		];
	}

	public static function relations(MapperInterface $mapper, EntityInterface $entity) {
		return [
			'tokens'    => $mapper->hasMany($entity, UserToken::class, 'user_id'),
			'modules'   => $mapper->hasMany($entity, Module::class, 'user_id'),
			'layouts'   => $mapper->hasMany($entity, Layout::class, 'user_id'),
		];
	}
}