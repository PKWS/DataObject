<?php

/**
 * Abstract class using to create models
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
abstract class A_DataObject
{
	/**
	 * Instance of db adapter
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $oDb;

	/**
	 * Primary Key definition
	 *
	 * @var array
	 */
	private $aPrimaryValue = array();

	/**
	 * The list of modified fields
	 *
	 * @var array
	 */
	private $aModifiedFields = array();

	/**
	 * Name of DB table
	 *
	 * @var string
	 */
	private $sTableName;

	/**
	 * Is object removed
	 *
	 * @var bool
	 */
	private $bDeleted = false;

	/**
	 * Whether the object is modified
	 *
	 * @var bool
	 */
	private $bModified = false;

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	string	$sTableName		name of DB table connected with model
	 * @param	array	$aPrimaryKey	array with prmiary key description (<field name> => <field value>)
	 * @return	A_DataObject
	 */
	public function __construct($sTableName, array $aPrimaryKey)
	{
		$this->oDb = Zend_Registry::get('db');

		$this->sTableName = $sTableName;
		$this->aPrimaryValue = $aPrimaryKey;
	}

	/**
	 * Do not allow serialization of a database object
	 */
	public function __sleep()
	{
		$oReflect = new ReflectionClass($this);
		$aResult = array();

		// analizuję pola klasy i odrzucam pole bazy danych
		foreach($oReflect->getProperties() as $oProperty)
		{
			if($oProperty->getName() != 'oDb')
			{
				$aResult[] = $oProperty->getName();
			}
		}

		return $aResult;
	}

	/**
	 * Loads database object after usnserialize
	 */
	public function __wakeup()
	{
		$this->oDb = Zend_Registry::get('db');
	}

	/**
	 * Delete object from DB
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->oDb->delete($this->sTableName, $this->getWhereString());
		$this->bDeleted = true;
	}

	/**
	 * Save object to DB
	 *
	 * @return	void
	 */
	public function save()
	{
		// is deleted
		if($this->bDeleted)
		{
			throw new A_DataObject_Exception('Object is already deleted, you cannot save it.');
		}

		// check whether any data has been modified
		if($this->bModified)
		{
			$this->oDb->update(
				$this->sTableName,
				$this->aModifiedFields,
				$this->getWhereString()
			);

			$this->aModifiedFields = array();
			$this->bModified = false;
		}
	}

	/**
	 * Returns DB table name
	 *
	 * @return	string
	 */
	protected function getTableName()
	{
		return $this->sTableName;
	}

	/**
	 * Returns true, if object was modified
	 *
	 * @return bool
	 */
	final protected function isModified()
	{
		return $this->bModified;
	}

	/**
	 * Set new DB field value
	 *
	 * @param	string	$sField		DB field name
	 * @param	string	$mValue		new field value
	 * @return	void
	 */
	final protected function setDataValue($sField, $mValue)
	{
		$this->aModifiedFields[$sField] = $mValue;
		$this->bModified = true;
	}

	/**
	 * Returns SQL WHERE code for Primary Key fields
	 *
	 * @return string
	 */
	private function getWhereString()
	{
		$oWhere = new A_DataObject_Where();

		foreach($this->aPrimaryValue as $sField => $sValue)
		{
			$oWhere->addAnd($sField . ' = ?', $sValue);
		}

		return $oWhere->getWhere();
	}
}
