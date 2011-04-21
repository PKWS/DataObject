<?php

/**
 * Paginator suitable for use in DataObject
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
class A_DataObject_Paginator implements Zend_Paginator_Adapter_Interface
{
	/**
	 * Array defines order
	 *
	 * @var array
	 */
	private $aOrder = array();

	/**
	 * All records count
	 *
	 * @var int
	 */
	private $iCount = null;

	/**
	 * Select for rows counting
	 *
	 * @var Zend_Db_Select
	 */
	private $oCountSelect = null;

	/**
	 * Input data Factory name
	 *
	 * @var A_DataObject_Factory
	 */
	private $oFactory = null;

	/**
	 * Where definition
	 *
	 * @var A_DataObject_Where
	 */
	private $oWhere = null;

	/**
	 * Optional parameters
	 *
	 * @var	mixed
	 */
	private $mOption = null;

	/**
	 * Constructor
	 *
	 * @param	A_DataObject_Factory	$oFactory		factory that creates this object
	 * @param	Zend_Db_Select			$oCountSelect	Select for rows counting
	 * @param	mixed					$mOption		additional options sended to getPage()
	 * @return	A_DataObject_Paginator
	 */
	public function __construct(A_DataObject_Factory $oFactory, Zend_Db_Select $oCountSelect, $mOption)
	{
		$this->oFactory		= $oFactory;
		$this->oCountSelect	= $oCountSelect;
		$this->mOption 		= $mOption;
	}

	/**
	 * Returns the total number of records in the query
	 *
	 * @return	int
	 */
	public function count()
	{
		if($this->iCount === null)
		{
			$this->iCount = Zend_Registry::get('db')->fetchOne($this->oCountSelect);
		}

		return $this->iCount;
	}

	/**
	 * Return an array of elements on a selected page
	 *
	 * @param	int		$iOffset		query offset
	 * @param	int		$iItemsPerPage	items limit per page
	 * @return	array
	 */
	public function getItems($iOffset, $iItemsPerPage)
	{
		$iPage = floor($iOffset / $iItemsPerPage) + 1;

		return $this->oFactory->getPage($iPage, $iItemsPerPage, $this->aOrder, $this->oWhere, $this->mOption);
	}

	/**
	 * Change query order
	 *
	 * @param	array	$aOrder
	 * @return	void
	 */
	public function setOrder(array $aOrder)
	{
		if($this->iCount !== null)
		{
			throw new A_DataObject_Exception('You cannot set ORDER after query execution');
		}

		$this->aOrder = $aOrder;
	}

	/**
	 * Change query where
	 *
	 * @param	string | A_DataObject_Where	$mWhere		where string or Where object
	 * @return	void
	 */
	public function setWhere($mWhere)
	{
		if($this->iCount !== null)
		{
			throw new A_DataObject_Exception('You cannot set WHERE after query execution');
		}

		if($mWhere instanceof A_DataObject_Where)
		{
			$this->oWhere = $mWhere;
		}
		else
		{
			$this->oWhere = new A_DataObject_Where($mWhere);
		}
	}
}
