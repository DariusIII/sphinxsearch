<?php

namespace dariusiii\SphinxSearch;

use Sphinx\SphinxClient;

class SphinxSearch
{
	protected $_connection;
	
	protected $_index_name;
	
	protected $_search_string;
	
	protected $_config;
	
	protected $_total_count;
	
	protected $_time;
	
	protected $_eager_loads;
	
	protected $_pdo;
	
	/**
	 * SphinxSearch constructor.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct()
	{
		$host = config('sphinxsearch.host');
		$port = config('sphinxsearch.port');
		$timeout = config('sphinxsearch.timeout');
		$this->_connection = new SphinxClient();
		$this->_connection->setServer($host, $port);
		$this->_connection->setConnectTimeout($timeout);
		$this->_connection->setMatchMode(SphinxClient::SPH_MATCH_ANY);
		$this->_connection->setSortMode(SphinxClient::SPH_SORT_RELEVANCE);
		if (config('sphinxsearch.mysql_server')) {
			$this->_pdo = new \PDO('mysql:host='.config('sphinxsearch.mysql_server.host').';port='.config('sphinxsearch.mysql_server.port').';charset=utf8', '', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => config('sphinxsearch.timeout'),]);
		}
		$this->_config = config('sphinxsearch.indexes');
		reset($this->_config);
		$this->_index_name = isset($this->_config['name']) ? implode(',', $this->_config['name']) : key($this->_config);
		$this->_eager_loads = [];
	}
	
	/**
	 * @param $docs
	 * @param $index_name
	 * @param $query
	 * @param array $extra , in this format: array('option_name' => option_value, 'limit' => 100, ...)
	 * @return array
	 */
	public function getSnippetsQL($docs, $index_name, $query, array $extra = []): array
	{
		// $extra = [];
		if (\is_array($docs) === false) {
			$docs = [$docs];
		}
		foreach ($docs as &$doc) {
			$doc = $this->_pdo->quote($doc);
		}
		unset($doc);
		
		$extra_ql = [];
		if ($extra) {
			foreach ($extra as $key => $value) {
				$extra_ql[] = $value.' AS '.$key;
			}
			$extra_ql = implode(',', $extra_ql);
			if ($extra_ql) {
				$extra_ql = ','.$extra_ql;
			}
		}
		
		$query = 'CALL SNIPPETS(('.implode(',', $docs)."),'".$index_name."','".$this->_pdo->quote($query)."' ".$extra_ql.')';
		// die($query);
		$result = $this->_pdo->query($query, \PDO::FETCH_ASSOC);
		// ddd($result);
		$reply = [];
		if ($result) {
			while ($row = $result) {
				$reply[] = $row['snippet'];
			}
		}
		
		return $reply;
	}
	
	/**
	 * @param $string
	 * @param null $index_name
	 * @return $this
	 */
	public function search($string, $index_name = null)
	{
		$this->_search_string = $string;
		if ($index_name !== null) {
			// if index name contains , or ' ', multiple index search
			if (strpos($index_name, ' ') || strpos($index_name, ',')) {
				if (! isset($this->_config['mapping'])) {
					$this->_config['mapping'] = false;
				}
			}
			$this->_index_name = $index_name;
		}
		$this->_connection->resetFilters();
		$this->_connection->resetGroupBy();
		
		return $this;
	}
	
	/**
	 * @param $weights
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setFieldWeights($weights)
	{
		$this->_connection->setFieldWeights($weights);
		
		return $this;
	}
	
	/**
	 * @param array $weights
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setIndexWeights(array $weights)
	{
		$this->_connection->setIndexWeights($weights);
		
		return $this;
	}
	
	/**
	 * @param $mode
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setMatchMode($mode)
	{
		$this->_connection->setMatchMode($mode);
		
		return $this;
	}
	
	/**
	 * @param $mode
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setRankingMode($mode)
	{
		$this->_connection->setRankingMode($mode);
		
		return $this;
	}
	
	/**
	 * @param $mode
	 * @param null $sortby
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setSortMode($mode, $sortby = null)
	{
		$this->_connection->setSortMode($mode, $sortby);
		
		return $this;
	}
	
	/**
	 * @param $attribute
	 * @param $min
	 * @param $max
	 * @param bool $exclude
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setFilterFloatRange($attribute, $min, $max, $exclude = false)
	{
		$this->_connection->setFilterFloatRange($attribute, $min, $max, $exclude);
		
		return $this;
	}
	
	/**
	 * @param $attrlat
	 * @param $attrlong
	 * @param null $lat
	 * @param null $long
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setGeoAnchor($attrlat, $attrlong, $lat = null, $long = null)
	{
		$this->_connection->setGeoAnchor($attrlat, $attrlong, $lat, $long);
		
		return $this;
	}
	
	/**
	 * @param $attribute
	 * @param $func
	 * @param string $groupsort
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setGroupBy($attribute, $func, $groupsort = '@group desc')
	{
		$this->_connection->setGroupBy($attribute, $func, $groupsort);
		
		return $this;
	}
	
	/**
	 * @param $select
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setSelect($select)
	{
		$this->_connection->setSelect($select);
		
		return $this;
	}
	
	/**
	 * @param $limit
	 * @param int $offset
	 * @param int $max_matches
	 * @param int $cutoff
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function limit($limit, $offset = 0, $max_matches = 1000, $cutoff = 1000)
	{
		$this->_connection->setLimits($offset, $limit, $max_matches, $cutoff);
		
		return $this;
	}
	
	/**
	 * @param $attribute
	 * @param $values
	 * @param bool $exclude
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function filter($attribute, $values, $exclude = false)
	{
		if (\is_array($values)) {
			$val = [];
			foreach ($values as $v) {
				$val[] = (int) $v;
			}
		} else {
			$val = [(int) $values];
		}
		$this->_connection->setFilter($attribute, $val, $exclude);
		
		return $this;
	}
	
	/**
	 * @param $attribute
	 * @param $min
	 * @param $max
	 * @param bool $exclude
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function range($attribute, $min, $max, $exclude = false)
	{
		$this->_connection->setFilterRange($attribute, $min, $max, $exclude);
		
		return $this;
	}
	
	/**
	 * @return mixed
	 * @throws \ErrorException
	 */
	public function query()
	{
		return $this->_connection->query($this->_search_string, $this->_index_name);
	}
	
	/**
	 * @param $content
	 * @param array $opts
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function excerpt($content, array $opts = [])
	{
		return $this->_connection->buildExcerpts([$content], $this->_index_name, $this->_search_string, $opts);
	}
	
	/**
	 * @param $contents
	 * @param array $opts
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function excerpts($contents, array $opts = [])
	{
		return $this->_connection->buildExcerpts($contents, $this->_index_name, $this->_search_string, $opts);
	}
	
	/**
	 * @param bool $respect_sort_order
	 * @return array|mixed
	 * @throws \ErrorException
	 */
	public function get($respect_sort_order = false)
	{
		$this->_total_count = 0;
		$result = $this->_connection->query($this->_search_string, $this->_index_name);
		// Process results.
		if ($result) {
			// Get total count of existing results.
			$this->_total_count = (int) $result['total_found'];
			// Get time taken for search.
			$this->_time = $result['time'];
			if ($result['total'] && isset($result['matches'])) {
				// Get results' id's and query the database.
				$matchids = array_keys($result['matches']);
				$idString = implode(',', $matchids);
				$config = $this->_config['mapping'] ?? $this->_config[$this->_index_name];
				
				// Get the model primary key column name
				$primaryKey = $config['primaryKey'] ?? 'id';
				
				if ($config) {
					if (isset($config['repository'])) {
						$result = \call_user_func($config['repository'].'::findInRange', $config['column'], $matchids);
					} else {
						if (isset($config['modelname'])) {
							if ($this->_eager_loads) {
								$result = \call_user_func($config['modelname'].'::whereIn', $config['column'], $matchids)->orderByRaw(\DB::raw("FIELD($primaryKey, $idString)"))->with($this->_eager_loads)->get();
							} else {
								$result = \call_user_func($config['modelname'].'::whereIn', $config['column'], $matchids)->orderByRaw(\DB::raw("FIELD($primaryKey, $idString)"))->get();
							}
						} else {
							$result = \DB::table($config['table'])->whereIn($config['column'], $matchids)->orderByRaw(\DB::raw("FIELD($primaryKey, $idString)"))->get();
						}
					}
				}
			} else {
				$result = [];
			}
		}
		if ($respect_sort_order) {
			if (isset($matchids)) {
				$return_val = [];
				foreach ($matchids as $matchid) {
					$key = $this->getResultKeyByID($matchid, $result);
					if (false !== $key) {
						$return_val[] = $result[$key];
					}
				}
				
				return $return_val;
			}
		}
		// important: reset the array of eager loads prior to making next call
		$this->_eager_loads = [];
		
		return $result;
	}
	
	/**
	 * @return $this
	 */
	public function with()
	{
		// Allow multiple with-calls
		if ($this->_eager_loads === null) {
			$this->_eager_loads = [];
		}
		
		foreach (\func_get_args() as $a) {
			// Add closures as name=>function()
			if (\is_array($a)) {
				$this->_eager_loads[] = array_merge($this->_eager_loads, $a);
			} else {
				$this->_eager_loads[] = $a;
			}
		}
		
		return $this;
	}
	
	/**
	 * @return mixed
	 */
	public function getTotalCount()
	{
		return $this->_total_count;
	}
	
	/**
	 * @return mixed
	 */
	public function getTime()
	{
		return $this->_time;
	}
	
	/**
	 * @return mixed
	 */
	public function getErrorMessage()
	{
		return $this->_connection->getLastError();
	}
	
	/**
	 * @param $id
	 * @param $result
	 * @return bool|int|string
	 */
	private function getResultKeyByID($id, $result)
	{
		if (\count($result) > 0) {
			foreach ($result as $k => $result_item) {
				if ($result_item->id === $id) {
					return $k;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * @param $string
	 * @return mixed
	 */
	public function escapeStringQL($string)
	{
		return $this->_connection->escapeString($string);
	}
}
