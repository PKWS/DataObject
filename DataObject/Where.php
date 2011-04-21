<?php

/**
 * Class that automates creating SQL conditions
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
class A_DataObject_Where
{
	/**
	 * Created SQL condition
	 *
	 * @var string
	 */
	private $sWhere = '';

	/**
	 * Instance of db adapter
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $oDb;

	/**
	 * Constructor
	 *
	 * @param	string|A_DataObject_Where	$mWhere		where string or Where object
	 * @param	string|null						$mValue		value for where string
	 * @return	A_dataObject_Where
	 */
	public function __construct($mWhere = null, $mValue = null)
	{
		$this->oDb = Zend_Registry::get('db');

		if($mWhere !== null)
		{
			$this->addAnd($mWhere, $mValue);
		}
	}

	/**
	 * It adds next element of the condition, preceded by a logical AND
	 *
	 * @param	string|A_DataObject_Where	$mWhere		where string or Where object
	 * @param	string|null						$mValue		value for where string
	 * @return	A_DataObject_Where
	 */
	public function addAnd($mWhere, $mValue = null)
	{
		if(!empty($this->sWhere))
		{
			$this->sWhere .= ' AND ';
		}

		$this->sWhere .= $this->parse($mWhere, $mValue);

		return $this;
	}

	/**
	 * It adds next element of the condition, preceded by a logical OR
	 *
	 * @param	string|A_DataObject_Where	$mWhere		where string or Where object
	 * @param	string|null						$mValue		value for where string
	 * @return	A_DataObject_Where
	 */
	public function addOr($mWhere, $mValue = null)
	{
		if(!empty($this->sWhere))
		{
			$this->sWhere .= ' OR ';
		}

		$this->sWhere .= $this->parse($mWhere, $mValue);

		return $this;
	}

	/**
	 * Returns created SQL condition
	 *
	 * @return string
	 */
	public function getWhere()
	{
		if(empty($this->sWhere))
		{
			return 'TRUE';
		}
		else
		{
			return $this->sWhere;
		}
	}

	/**
	 * Negates the actual condition
	 *
	 * @return	void
	 */
	public function negate()
	{
		if($this->sWhere == '')
		{
			$this->sWhere = 'FALSE';
		}
		else
		{
			$this->sWhere = 'NOT (' . $this->sWhere . ')';
		}
	}

	/**
	 * Parse the pased value to the part of SQL command
	 *
	 * @param	string|A_DataObject_Where	$mWhere		where string or Where object
	 * @param	string|null						$mValue		value for where string
	 * @return	string
	 */
	private function parse($mWhere, $mValue)
	{
		$sResult = '';

		if($mWhere instanceof A_DataObject_Where)
		{
			$sResult .= '('. $mWhere->getWhere() .')';
		}
		else
		{
			$sResult = $this->oDb->quoteInto($mWhere, $mValue);
		}

		return $sResult;
	}
}
