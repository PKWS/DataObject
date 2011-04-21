<?php

/**
 * Abstract class using to create factory for models
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
abstract class A_DataObject_Factory
{
	/**
	 * Instance of db adapter
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $oDb = null;

	/**
	 * Primary Key definition
	 *
	 * @var array
	 */
	private $aPrimaryKey = null;

	/**
	 * Select object for paginator
	 *
	 * @var Zend_Db_Select
	 */
	private $oPaginatorSelect = null;

	/**
	 * Select object (will be cloned)
	 *
	 * @var Zend_Db_Select
	 */
	private $oSelect = null;

	/**
	 * DB table name
	 *
	 * @var string
	 */
	private $sTableName = null;

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	string	$sTableName		name of DB table connected with model
	 * @param	array	$aPrimaryKey	array with primay key fields
	 * @return	A_DataObject_Factory
	 */
	public function __construct($sTableName, array $aPrimaryKey)
	{
		$this->oDb = Zend_Registry::get('db');

		$this->sTableName = $sTableName;
		$this->aPrimaryKey = $aPrimaryKey;
	}

// Factory method

	/**
	 * Returns an array of objects with specified ID
	 *
	 * @param	array	$aIds		array with ID/IDs
	 * @param	array	$aOrder		array with order definition
	 * @return	array
	 */
	public function getFromIds(array $aIds, array $aOrder = array())
	{
		if(empty($aIds))
		{
			return array();
		}

		$oSelect = $this->getSelect()
				->where($this->getWhereString($aIds));

		if(!empty($aOrder))
		{
			$oSelect->order($aOrder);
		}

		$aDbRes = $oSelect->query()->fetchAll();

		return $this->createList($aDbRes);
	}

	/**
	 * Returns an array of object that matches the given condition
	 *
	 * @param	string|A_DataObject_Where	$oWhere		where string or Where object
	 * @return	array
	 */
	public function getFromWhere($mWhere, array $aOrder = array())
	{
		if($mWhere instanceof A_DataObject_Where)
		{
			$mWhere = $mWhere->getWhere();
		}

		$oSelect = $this->getSelect()->where($mWhere);

		if(!empty($aOrder))
		{
			$oSelect->order($aOrder);
		}

		$aDbRes = $oSelect->query()->fetchAll();

		return $this->createList($aDbRes);
	}

	/**
	 * Returns a single object with the specified ID
	 *
	 * @param	mixed	$mId	specific key value or an array (<field> => <value>)
	 * @return	A_DataObject
	 */
	public function getOne($mId)
	{
		$aDbRes = $this->getSelect()
				->where($this->getWhereString($mId))
				->query()->fetchAll();

		$aResult = $this->createList($aDbRes);

		if(!isset($aResult[0]))
		{
			throw new A_DataObject_Exception('The object with the specified ID does not exist');
		}

		return $aResult[0];
	}

	/**
	 * Returns one page for paginator
	 *
	 * @param	int								$iPage		page number
	 * @param	int								$iCount		number of results per page
	 * @param	array							$aOrder		array with order definition
	 * @param	string|A_DataObject_Wheret	$oWhere		where string or Where object
	 * @param	mixed							$mOption	optional parameters
	 * @return	array
	 */
	public function getPage($iPage, $iCount, array $aOrder = array(), $mWhere = null, $mOption = null)
	{
		$oSelect = $this->getSelect();
		$oSelect->limitPage($iPage, $iCount);

		// adds order
		foreach($aOrder as $sOrder)
		{
			$oSelect->order($sOrder);
		}

		// adds where
		if($mWhere !== null)
		{
			if($mWhere instanceof A_DataObject_Where)
			{
				$mWhere = $mWhere->getWhere();
			}

			$oSelect->where($mWhere);
		}

		$aResult = $oSelect->query()->fetchAll();

		return $this->createList($aResult);
	}

// additional methods

	/**
	 * Returns a paginator set on a particular page
	 *
	 * @param	int								$iPage		page number
	 * @param	int								$iCount		number of results per page
	 * @param	array							$aOrder		array with order definition
	 * @param	string|A_DataObject_Wheret	$oWhere		where string or Where object
	 * @param	mixed							$mOption	optional parameters sended to getPage()
	 * @return	array
	 */
	public function getPaginator($iPage, $iCount, array $aOrder = array(), $mWhere = null, $mOption = null)
	{
		$oSelect = $this->getCountSelect($mOption);

		if($mWhere !== null)
		{
			if($mWhere instanceof A_DataObject_Where)
			{
				$mWhere = $mWhere->getWhere();
			}

			$oSelect->where($mWhere);
		}

		$oInterface = new A_DataObject_Paginator($this, $oSelect, $mOption);
		$oInterface->setOrder($aOrder);

		if($mWhere !== null)
		{
			$oInterface->setWhere($mWhere);
		}

		$oPaginator = new Zend_Paginator($oInterface);
		$oPaginator->setCurrentPageNumber($iPage)
					->setItemCountPerPage($iCount);

		return $oPaginator;
	}

	/**
	 * Creates an array of objects from the results returned by the database
	 *
	 * @param	array	$aDbResult	results returned by the database
	 * @return array
	 */
	protected function createList(array &$aDbResult)
	{
		$aResult = array();

		foreach($aDbResult as $aRow)
		{
			$aResult[] = $this->createObject($aRow);
		}

		return $aResult;
	}

	/**
	 * Create object from DB row
	 *
	 * @param	array	$aRow	one row from database
	 * @return	A_DataObject
	 */
	abstract protected function createObject(array $aRow);

	/**
	 * Returns DB table name
	 *
	 * @return	string
	 */
	final protected function getTableName()
	{
		return $this->sTableName;
	}

	/**
	 * Returns an array with describe Primary Key
	 *
	 * @return	array
	 */
	final protected function getPrimaryKey()
	{
		return $this->aPrimaryKey;
	}

	/**
	 * Returns a Select object
	 *
	 * @param	mixed	$mFields	fields to select
	 * @return	Zend_Db_Select
	 */
	protected function getSelect($mFields = '*')
	{
		if($this->oSelect === null)
		{
			$oSelect = new Zend_Db_Select($this->oDb);
		}
		else
		{
			$oSelect = clone $this->oSelect;
		}

		$oSelect->from($this->sTableName, $mFields);

		return $oSelect;
	}

	/**
	 * Returns a Select object for Paginator Count
	 *
	 * @param	mixed	$mOption	additional options
	 * @return	Zend_Db_Select
	 */
	protected function getCountSelect($mOption = null)
	{
		return $this->getSelect()
						->reset(Zend_Db_Select::COLUMNS)
						->columns(new Zend_Db_Expr('COUNT(*)'));
	}


	/**
	 * Returns SQL WHERE string created for the specified key fields
	 *
	 * @return string
	 */
	protected function getWhereString($mId)
	{
		$oWhere = new A_DataObject_Where();

		if(count($this->aPrimaryKey) > 1)
		{
			// many fields in key
			foreach($mId as $aKeys)
			{
				$oWhere2 = new A_DataObject_Where();

				foreach($this->aPrimaryKey as $sField)
				{
					if(!isset($aKeys[$sField]))
					{
						throw new A_DataObject_Exception('No value for key part: ' . $sField);
					}

					// where for a single field
					$sTmp = $this->getTableName() .'.'. $sField;
					$sTmp .= is_array($aKeys[$sField]) ? ' IN (?)' : ' = ?';

					$oWhere2->addAnd($sTmp, $aKeys[$sField]);
				}

				$oWhere->addOr($oWhere2);
				unset($oWhere2);
			}
		}
		else
		{
			// tylko jedne wiersz w tabeli jest kluczem
			$sTmp = $this->getTableName() .'.'. $this->aPrimaryKey[0];
			$sTmp .= is_array($mId) ? ' IN (?)' : ' = ?';

			$oWhere->addAnd($sTmp, $mId);
		}

		return $oWhere->getWhere();
	}

	/**
	 * Returns on Instance of Factory
	 *
	 * @return A_DataObject_Factory
	 */
	public static function getNew()
	{
		return new static();
	}
}
