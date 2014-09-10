<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/lib/txplib_db.php $
$LastChangedRevision: 3250 $
*/

include txpath.'/lib/txplib_db_safe_rows_tree.php';

if (get_magic_quotes_runtime()) {

	set_magic_quotes_runtime(0);
}

class DB {

	function DB()
	{
		global $txpcfg;
		
		if (!isset($txpcfg['db']))
		{
			@include txpath.'/config.php';
		}
		
		$this->host = $txpcfg['host'];
		$this->db	= $txpcfg['db'];
		$this->user = $txpcfg['user'];
		$this->pass = $txpcfg['pass'];
		$this->client_flags = isset($txpcfg['client_flags']) ? $txpcfg['client_flags'] : 0;

		$this->link = @mysql_connect($this->host, $this->user, $this->pass, false, $this->client_flags);
		
		if (!$this->link) die(db_down());

		$this->version = mysql_get_server_info();

		if (!$this->link)
			$GLOBALS['connected'] = false;
		else
			$GLOBALS['connected'] = true;
		
		@mysql_select_db($this->db) or die(db_down());

		$version = $this->version;
		// be backwardscompatible
		if ( isset($txpcfg['dbcharset']) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)) )
			mysql_query("SET NAMES ". $txpcfg['dbcharset']);
	}
	
	function refresh() {
		
		global $txpcfg, $connected;
		
		$this->host = $txpcfg['host'];
		$this->db	= $txpcfg['db'];
		$this->user = $txpcfg['user'];
		$this->pass = $txpcfg['pass'];
		
		$this->db = $txpcfg['db'];
		
		$this->link = @mysql_connect($this->host, $this->user, $this->pass, false, $this->client_flags);
		
		if (!$this->link) die(db_down());
		
		$this->version = mysql_get_server_info();
		
		$connected = (!$this->link) ? false : true;
		
		@mysql_select_db($this->db) or die(db_down());
		
		$version = $this->version;
		// be backwardscompatible
		if ( isset($txpcfg['dbcharset']) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)) )
			mysql_query("SET NAMES ". $txpcfg['dbcharset']);
	}
}

$GLOBALS['DB'] = new DB;

//-------------------------------------------------------------
	function remove_pfx($tables,$pfx=null) 
	{
		global $PFX;
		
		$pfx = (is_null($pfx)) ? $PFX : $pfx;
		
		if ($pfx) {
		
			$tables = do_list($tables);
			
			foreach ($tables as $key => $table) {
			
				$tables[$key] = preg_replace('/^'.$pfx.'/','',$table);
			}
		}
		
		return $tables;
	}

//-------------------------------------------------------------
// add prefix to table names that are not enclosed in single quotes

	function add_pfx($q,$pfx=null) 
	{	
		global $PFX, $tables;
		
		$pfx = (is_null($pfx)) ? $PFX : $pfx;
		
		if ($pfx) {
		
			foreach ($tables as $table) {
				$q = preg_replace('/(?<!\')\b('.$table.')\b(?!\')/',$pfx."$1",$q);
			}
		}
		
		return $q;
	}

//-------------------------------------------------------------
	function safe_pfx($table,$pfx=null) 
	{
		global $PFX;
		
		/* if ($table == 'txp_site' and is_null($pfx)) {
			
			return trim($table);
		} */
		
		$pfx = (is_null($pfx)) ? $PFX : $pfx;
		
		if ($pfx) {
		
			$table = $pfx.trim($table);

			if (preg_match('@[^\w._$]@',$table))
				$table = $table;
		}

		return $table;
	}
	
//-------------------------------------------------------------
// no AS for table that already has AS

	function safe_pfx_j($table,$pfx=null)
	{
		global $PFX;
		
		$pfx = (is_null($pfx)) ? $PFX : $pfx;
		
		if ($pfx and $pfx != 'NONE') {
		
			$joins = explode(' JOIN ',$table);
			
			if (count($joins) == 1) {
				$joins = explode(',',$joins[0]);
			}
			
			foreach ($joins as $i => $table) {
				
				// if ($table == 'txp_site') continue;
				
				if (str_begins_with(trim($table),'(')) continue;
				
				$table = $pfx.trim($table);
				
				if (preg_match('/\s(as|AS)\s/',$table)) 		
					$joins[$i] = $table;
				elseif (preg_match('@[^\w._$]@',$table))
					$joins[$i] = "$table"; 		// .($pfx ? " AS `$t`" : '');
				else
					$joins[$i] = "$table"; 		// .($pfx ? " AS `$t`" : '');
			}
			
			$table = implode(' JOIN ',$joins);
		}
		
		return $table;
	}

// -------------------------------------------------------------
// add prefix to returned column names 

	function add_var_pfx($rs,$prefix)
	{	
		$out = array();	
		
		if (!strlen($prefix)) return $rs;
		if (is_numeric($prefix) or is_bool($prefix)) return $rs;
		
		foreach ($rs as $key => $item) {
			
			if (is_array($item)) {
			
				foreach ($item as $name => $value) {
					
					$out[$key][$prefix.$name] = $value;
				}
			
			} else {
				
				$out[$prefix.$key] = $item;
			}
		}
		
		return $out;
	}

//--------------------------------------------------------------
	function log_query($q,$type,$result)
	{
		global $txp_user,$event,$step,$log_buffer,$app_mode;
		
		$exclude = array(
			'update_path',
			'update_path_columns',
			'rebuild_txp_tree',
			'apply_custom_fields',
			'renumerate',
			'add_category_count',
			'insert_logit',
			'logit_agent',
			'store_session_data',
			'update_lastmod'
		);
		
		preg_match('/\b([a-z0-9_]+)?txp_([a-z_]+)\b/',$q,$matches);
		$table = (isset($matches[2])) ? $matches[2] : 'textpattern'; 
		
		if (!in_list($table,'window')) {
		
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$func = '';
			
			$backtrace = (defined("DEBUG_BACKTRACE_IGNORE_ARGS"))
				? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) 
				: debug_backtrace();
			
			array_shift($backtrace);
      		
      		foreach ($backtrace as $key => $item) {
				
				$backtrace[$key] = $item['function'];
				
				if ($key == 0) $backtrace[$key] .= '.'.$item['line'];
				if ($key == 1) $func = $item['function'];
			}
			
			$backtrace = implode('/',array_reverse($backtrace));	
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$func  = str_pad($func,30);
			$q     = str_pad(preg_replace("/(\n|\s+)/",' ',$q.';'),200);
			$date  = date("Y/m/d H:i:s");
			$user  = str_pad($txp_user,10);
			$rows  = ($result) ? str_pad($result,4) : '-   ';
			$id    = '-   ';
			
			if ($type == 'insert') {
				$id = str_pad($result,4);
				$rows = '1   ';
			}	
				
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (!$log_buffer) {
				
				$entry = '';
				
				$step_in = (gps('step')) ? '('.gps('step').') '.$step : $step;
				
				$entry .= n.str_pad('=',400,'=');
				$entry .= n."$date | $user | $event $step_in | $app_mode";
				$entry .= n.str_pad('-',400,'-');
				
				$log_buffer[] = $entry;
			}
			
			if ($table != 'cache') {
				
				if (!in_array(trim($func),$exclude)) {
				
					$log_buffer[] = n."$date | $user | $func | $rows | $id | $q ($backtrace)";
				}
			}
		}
		
	}

//--------------------------------------------------------------
	function save_log_buffer()
	{
		global $log_buffer,$path_to_site;
		
		$path_to_log = $path_to_site.DS.'log';
		
		if (!is_dir($path_to_log)) {
			
			if (!is_writable($path_to_site)) return;
				
			mkdir($path_to_log,0777);
		}
		
		if (!is_writable($path_to_log)) return;
		
		$file = $path_to_log.DS.date("Y_m_d").'.txt';
		
		if (count($log_buffer) > 1) {
			write_to_file($file,implode('',$log_buffer),0,1);
			@chmod($file,0777);
		}
	}
	
//--------------------------------------------------------------
/*	function log_query_old($q,$type,$result)
	{
		global $path_to_site,$txp_user,$event,$step;
		
		static $new  = true;
		static $prev = array('func'=>'');
		
		$exclude = array(
			'update_path',
			'update_path_columns',
			'rebuild_txp_tree',
			'apply_custom_fields',
			'renumerate',
			'add_category_count',
			'insert_logit',
			'store_session_data',
			'update_lastmod'
		);
		
		// if (!$rows and !$new) return;
		
		$path_to_log = $path_to_site.DS.'log';
		
		if (!is_dir($path_to_log)) {
			
			if (!is_writable($path_to_site)) return;
				
			mkdir($path_to_log,0777);
		}
		
		if (!is_writable($path_to_log)) return;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		preg_match('/\b([a-z0-9_]+)?txp_([a-z_]+)\b/',$q,$matches);
		$table = (isset($matches[2])) ? $matches[2] : 'textpattern'; 
		
		if (!in_list($table,'window')) {
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$func = '';
			
			$backtrace = (defined("DEBUG_BACKTRACE_IGNORE_ARGS"))
				? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) 
				: debug_backtrace();
			
			array_shift($backtrace);
      		
      		foreach ($backtrace as $key => $item) {
				
				$backtrace[$key] = $item['function'];
				
				if ($key == 0) $backtrace[$key] .= '.'.$item['line'];
				if ($key == 1) $func = $item['function'];
			}
			
			$backtrace = implode('/',array_reverse($backtrace));	
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$file = $path_to_log.DS.date("Y_m_d").'.txt';
			
			$func  = str_pad($func,30);
			$q     = str_pad(preg_replace("/(\n|\s+)/",' ',$q.';'),200);
			$date  = date("Y/m/d H:i:s");
			$user  = str_pad($txp_user,10);
			$rows  = ($result) ? str_pad($result,4) : '-   ';
			$id    = '-   ';
			
			if ($type == 'insert') {
				$id = str_pad($result,4);
				$rows = '1   ';
			}	
				
			$entry = '';
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($prev['func'] != trim($func)) {
			
				if (in_array($prev['func'],$exclude)) {
			
					extract($prev,EXTR_PREFIX_ALL,'prev');
					
					$prev_func  = str_pad($prev_func.' x '.$prev_count,30);
					$prev_rows  = ($prev_rows) ? str_pad($prev_rows,4) : '-   ';
					
					$entry .= n."$prev_date | $prev_user | $prev_func | $prev_rows | -    | $prev_q";
				}
				
				$prev = array(
					'date'  	=> $date,
					'user'  	=> $user,
					'func'  	=> trim($func),
					'count' 	=> 1,
					'rows' 		=> trim($rows),
					'q'			=> $q
				);	
			
			} else {
			
				$prev['count'] += 1;
				$prev['rows']  += trim($rows);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($new) {
				
				$step_in = (gps('step')) ? '('.gps('step').') '.$step : $step;
				
				$entry .= n.str_pad('=',400,'=');
				$entry .= n."$date | $user | $event $step_in ";
				
				if ($step == 'event_multi_edit' or $step == 'multi_edit') {
					
					$method   = '';
					$selected = '';
					
					if (!$method = gps('edit_method')) {
						if (preg_match('/\/apply\/do_([a-z]+)\//',$path,$matches)) {
							$method = $matches[1];
						}
					}
					
					if ($selected = gps('selected')) {
						
						$selected = " (".implode(',',do_list($selected)).")";
					}
					
					$entry .= $method.$selected;
				}
			
				if ($table != 'cache') {
					$entry .= n.str_pad('-',400,'-');
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($table != 'cache') {
				
				if (!in_array(trim($func),$exclude)) {
				
					$entry .= n."$date | $user | $func | $rows | $id | $q ($backtrace)";
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($entry) write_to_file($file,$entry,0,1);
			
			@chmod($file,0777);
			
			$new = false;
		}
	}	
*/			
//-------------------------------------------------------------
	function safe_query($q='',$debug='',$unbuf='')
	{
		global $DB, $txpcfg, $qcount, $qtime, $production_status, $dump; 
		static $qnum = 1;
		$method = (!$unbuf) ? 'mysql_query' : 'mysql_unbuffered_query';
		if (!$q) return false;
		
		if ($debug or TXP_DEBUG >= 1) {
			
			if (TXP_DEBUG == 1 or (defined('TXP_UPDATE') and TXP_UPDATE == 1)) {
			
				dmp($q);
			
			} elseif (TXP_DEBUG == 2) { 
				
				inspect(backtrace()); 
				inspect('<pre>'.$q.'</pre>'); 
				inspect(mysql_error());
			
			} elseif ($debug == 1) {
				
			 // pre(backtrace()); 
				
				dmp($q);
			
			} elseif ($debug == 2) { 
				
				inspect(backtrace());
				inspect('<pre>'.$q.'</pre>'); 
				inspect(mysql_error()); 
			}
		
		} elseif (gps('sql')) {
				
			echo ("<pre>$qnum: $q</pre>");
			
			$qnum += 1;
		}
		
		$start = getmicrotime();
		$result = $method($q,$DB->link); 
		$time = getmicrotime() - $start;
		@$qtime += $time;
		@$qcount++;
		if ($result === false and ((defined('txpinterface') and txpinterface === 'admin') or @$production_status == 'debug' or @$production_status == 'testing')) {
			$caller = ($production_status == 'debug') ? n . join("\n", get_caller()) : '';
			trigger_error(mysql_error() . n . $q . $caller, E_USER_WARNING);
			inspect(backtrace());
		}
		
		// trace_add("[SQL ($time): $q]");

		if(!$result) return false;
		return $result;
	}

// -------------------------------------------------------------
	function safe_delete($table, $where, $debug='', $delete='')
	{
		if (is_array($where)) {
			
			foreach ($where as $name => $value) {
				$where[$name] = str_pad('`'.trim($name).'`',15).' = '.doQuote($value);
			}
			
			$where = n.implode(' AND '.n,$where);
		}
		
		$q = "DELETE $delete FROM ".safe_pfx_j($table)." WHERE $where";
		
		if (safe_query($q,$debug)) {
			
			$rows = mysql_affected_rows();
			
			log_query($q,'delete',$rows);
			
			return $rows;
		}
		
		return false;
	}

// -------------------------------------------------------------
	function safe_update_parents($table, $set, $where, $debug='')
	{
		$ids = safe_column("ParentID",$table,$where);
		
		foreach ($ids as $id) {
			
			if ($id != 0) {
			
				$where = "ID = $id";
				
				safe_update($table,$set,"ID = $id",$debug);
				
				safe_update_parents($table,$set,$where,$debug);
			}
		}
	}
	
// -------------------------------------------------------------
	function safe_update($table, $set, $where, $debug='')
	{
		if (is_array($set)) {
			
			foreach ($set as $name => $value) {
				if (strlen($value) == 0) $value = "''";
				$set[$name] = str_pad('`'.trim($name).'`',15).' = '.$value;
			}
			
			$set = implode(','.n,$set);
		}
		
		$q = "UPDATE ".safe_pfx_j($table)." SET $set WHERE $where";
		
		if (safe_query($q,$debug)) {
			
			$rows = mysql_affected_rows();
			
			log_query($q,'update',$rows);
			
			return $rows;
		}
		
		return false;
	}

// -------------------------------------------------------------
	function updated_rows()
	{
		global $DB;
		
		return mysql_affected_rows($DB->link);
	}

// -------------------------------------------------------------
	function safe_update_multi($table, $column, $value, $where, $key='ID', $debug='')
	{
		$out = array();
		
		foreach ($where as $val) {
			
			if (safe_update($table, "$column = '".doSlash($value)."'", "$key = '$val'", $debug))
			{
				$out[] = $val;
			}
		}
		
		return $out;
	}
						
// -------------------------------------------------------------
	function safe_insert($table,$set,$debug=0)
	{
		global $DB;
		
		mysql_query("LOCK TABLES ".safe_pfx($table)." WRITE");
		
		if (is_array($set)) {
			
			foreach ($set as $name => $value) {
				
				$value = trim(trim(trim($value),"'"));
				
				if (str_begins_with($value,'SELECT')) {
				
					if ($r = safe_query($value,$debug)) {
						$value = (mysql_num_rows($r) > 0) ? mysql_result($r,0) : '';
						mysql_free_result($r);
					} else {
						$value = doQuote($value);
					}
				
				} elseif (!is_numeric($value)) {
					
					if (!preg_match('/^(now|from_unixtime)\(/',strtolower($value))) {
						
						if (substr($value,0,8) != 'ADDTIME(') {
							
							$value = doQuote($value);
						}
					}
				}
				
				$set[$name] = str_pad('`'.trim($name).'`',15).' = '.$value;
			}
			
			$set = n.implode(','.n,$set);
		}
		
		$q = "INSERT INTO ".safe_pfx($table)." SET $set";
		
		if ($r = safe_query($q,$debug)) {
			
			$id = mysql_insert_id($DB->link);
			
			mysql_query("UNLOCK TABLES");
			
			log_query($q,'insert',$id);
			
			return ($id === 0 ? true : $id);
		}
		
		mysql_query("UNLOCK TABLES");
		
		return false;
	}

// -------------------------------------------------------------
// insert or update

	function safe_upsert($table,$set,$where,$debug='')
	{
		// FIXME: lock the table so this is atomic?
		$r = safe_update($table, $set, $where, $debug);
		if ($r and (mysql_affected_rows() or safe_count($table, $where, $debug)))
			return $r;
		else
			return safe_insert($table, join(', ', array($where, $set)), $debug);
	}

// -------------------------------------------------------------
	function safe_alter($table, $alter, $debug=1)
	{
		if (!strlen($alter)) return 0;
		
		$alter = do_list($alter,'');
		$count = 0;	
		
		foreach (do_list($table) as $table) {
			
			if (table_exists($table)) {
				
				foreach ($alter as $alteration) {
				 	
				 	$table = safe_pfx($table);
				 	
					if (safe_query("ALTER TABLE `$table` $alteration",$debug)) {
						$count += 1;
					}
				}
			}
		}
		
		return $count;
	}

// -------------------------------------------------------------
	function safe_databases()
	{
		$databases = getThings("SHOW DATABASES");
		$databases = array_slice($databases,1);
		
		return array_combine($databases,$databases);
	}
	
// -------------------------------------------------------------
	function safe_tables($exclude='',$pfx=null,$debug='')
	{
		global $PFX;
		
		$pfx = (is_null($pfx)) ? $PFX : $pfx;
		
		if ($pfx) {
		
			$pfx = rtrim($pfx,'_').'_';
			
			$tables = getThings("SHOW TABLES LIKE '".$pfx."%'");
			
			foreach ($tables as $key => $table) {
				$tables[$key] = substr($table,strlen($pfx));
			}
		
		} else {
			
			$tables = getThings("SHOW TABLES LIKE 'txp_%'");
			array_unshift($tables,"textpattern");
		}
		
		if ($exclude) {
			
			$tables = array_flip($tables);
			
			foreach(do_list($exclude) as $table) {
				
				if (isset($tables[$table])) { 
					unset($tables[$table]);
				}
			}
					
			$tables = array_flip($tables);
		}
		
		return $tables;
	}

// -------------------------------------------------------------
	function safe_create($table,$items,$debug=1)
	{
		// Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2)
		
		$mysqlversion = mysql_get_server_info();
		$tabletype = ( intval($mysqlversion[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$mysqlversion))
			? " ENGINE=MyISAM"
			: " TYPE=MyISAM";
			
		$tabletype .= ' DEFAULT CHARSET=utf8';
					
		if (is_array($items)) {
			
			$longest = 0;
			
			foreach ($items as $key => $item) {
				$item = preg_replace('/\s+/',' ',$item);
				$item = explode(' ',$item);
				$col  = array_shift($item);
				$items[$col] = implode(' ',$item);
				if (strlen($col) > $longest) $longest = strlen($col);
				unset($items[$key]);
			}
			
			foreach ($items as $col => $item) {
				$items[$col] = '  '.str_pad($col,$longest,' ').' '.$item;
			}
			
			$items = n.implode(','.n,$items).n;
		}
		
		safe_query("CREATE TABLE `".safe_pfx($table)."` ($items) $tabletype;",$debug);
			
		$GLOBALS['tables'][] = $table;
	}

// -------------------------------------------------------------
// drop every table having the same prefix

	function safe_drop_pfx($pfx,$debug=0)
	{
		$pfx = rtrim($pfx,'_').'_';
		
		$tables = getThings("SHOW TABLES LIKE '$pfx%'");
		
		foreach ($tables as $table) {
			
			$table = preg_replace('/^'.$pfx.'/','',$table);
			
			safe_drop($table,'',$pfx,0);
		}
	}
	
// -------------------------------------------------------------
	function safe_drop($table, $col='', $pfx=null, $debug=1)
	{
		global $tables;
		
		$count = 0;
		
		foreach (do_list($table) as $table) {
			
			if (table_exists($table,$pfx)) {
				
				$columns = getColumns($table,$pfx); 
			
				if ($col) {
					
					if (in_array($col,$columns)) {
					
						if (safe_query("ALTER TABLE `".safe_pfx($table,$pfx)."` DROP COLUMN `$col`",$debug)) {
							$count += 1;
						}
					}
					
				} else {
					
					if (safe_query("DROP TABLE `".safe_pfx($table,$pfx)."`",$debug)) {
						
						$tables = array_flip($tables);
						unset($tables[$table]);
						$tables = array_flip($tables);
						
						$count += 1;
					}
				}
			}
		}
		
		return $count;
	}
	
// -------------------------------------------------------------
	function safe_index($table, $key, $col='', $debug=0)
	{
		if (!index_exists($table,$key)) {
				
			$col = ($col) ? $col : $key;
				
			return safe_alter($table,"ADD INDEX $key ($col)",$debug);
		}
		
		return false;
	}

// -------------------------------------------------------------
	function safe_unindex($table, $key='', $debug='')
	{
		$keys    = array();
		$count   = 0;
		$primary = false;
		
		if (!$key) {
			
			$rows = getRows("SHOW INDEX FROM ".safe_pfx($table));
			
			if (is_array($rows) and count($rows)) {
				
				foreach($rows as $row) {
					
					$name = $row['Key_name'];
					
					if ($name == 'PRIMARY') {
						
						$primary = true;
					
					} else {
						
						$keys[$name] = $name;
					}
				}
			}
			
		} else {
			
			$keys = do_list($key);
		}
		
		foreach($keys as $key) {
		
			if (index_exists($table,$key)) {
					
				$count += safe_alter($table,"DROP KEY `$key`",$debug);
			}
		}
		
		if ($primary) {
			
			safe_alter($table,"DROP PRIMARY KEY");
		}
		
		return $count;
	}

// -------------------------------------------------------------
	function safe_optimize($table, $debug='')
	{
		$table = safe_pfx($table);
		
		if (safe_query("OPTIMIZE TABLE $table",$debug)) {
		
			return true;
		}
		
		return false;
	}

// -------------------------------------------------------------
	function safe_repair($table, $debug='')
	{
		$table = safe_pfx($table);
		
		if (safe_query("REPAIR TABLE $table",$debug)) {
		
			return true;
		}
		
		return false;
	}

// -------------------------------------------------------------
	function safe_modcol($table,$col,$mod,$debug=1)
	{
		$count = 0;
		
		if (!$mod) return 0;
		
		$mod = preg_replace('/\s\s+/',' ',$mod);
		
		foreach (do_list($table) as $table) {
		
			if (column_exists($table,$col)) {
			
				if (safe_alter($table,"MODIFY COLUMN `$col` $mod",$debug)) {
				
					$count += 1;
				}
			}
		}
		
		return $count;
	}
	
// -------------------------------------------------------------
	function safe_modcol_OLD($table,$col,$mod,$debug=1)
	{
		$count = 0;
		
		foreach (do_list($table) as $table) {
			
			$info = getColumnInfo($table,$col);
			
			if (!$info) continue;
			
			$mod  = do_list($mod);
			
			foreach ($mod as $key => $item) {
				
				$test = strtolower($item);
				
				if (in_list($test,'null,not null')) {
				
					$name  = 'null';
					$value = $item; 
				
				} elseif (str_begins_with($test,'default')) {
				
					$name  = 'default';
					$value = trim(trim(substr($item,7)),"'"); 
				
				} elseif (str_begins_with($test,'after')) {
				
					$name  = 'after';
					$value = trim(substr($item,5)); 
				
				} elseif ($test == 'first') {
					
					$name  = 'first';
					$value = 1; 
				
				} else {
					
					$name  = 'type';
					$value = $item;
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				$old_val = strtolower($info[$name]);
				$new_val = strtolower($value); 
				
				if ($name == 'type') {
				
					$old_type = reset(explode('(',$old_val));
					$new_type = reset(explode('(',$new_val));
					
					if (in_list($old_type,'int,tinyint')) {
						
						$old_val = $old_type;
						
						if (in_list($new_type,'int,tinyint')) {
							$new_val = $new_type;
						}
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($old_val != $new_val) { 
					
					$info[$name] = $value;
					
					if ($name == 'after') {
						
						if (!column_exists($table,$value)) {
							unset($mod[$key]);
							unset($info[$name]);
						} else {
							$info[$name] = "AFTER `$value`";
						}
						
					} elseif ($name == 'first') {
						
						$info['first'] = "FIRST";
					}
					
				} else {
					
					unset($mod[$key]);
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$info['field']  = "`$col`";
				
			// - - - - - - - - - - - - - - - - - - - - - - -
			
			$default = $info['default'];
			
			if (strtolower($default) == 'null') {
				$info['default'] = "DEFAULT NULL";
			} elseif (is_numeric($default)) {
				$info['default'] = "DEFAULT $default";
			} elseif (strlen($default)) {
				$info['default'] = "DEFAULT '$default'";
			} else {
				unset($info['default']);
			}
			
			unset($info['key']);
			
			if (!str_begins_with($info['after'],'AFTER ')) unset($info['after']);
			if ($info['first'] !== 'FIRST') unset($info['first']);
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($mod) {
			
				$alter = "MODIFY COLUMN ".implode(' ',$info);
					
				if (safe_alter($table,$alter,$debug)) {
				
					$count += 1;
				}
			}
		}
		
		return $count;	
	}
	
// -------------------------------------------------------------
	function safe_addcol($table,$col,$type,$after=null,$debug=1)
	{
		static $prev_table = '';
		static $prev_col   = '';
		
		$count   = 0;
		
		$info    = preg_split('/\s+/',trim($type));
		
		$type    = array_shift($info);
		$null    = "NOT NULL";
		$default = '';
		$extra   = '';
		
		if (count($info)) {
			
			if (strtolower($info[0]) == 'not') {
				array_shift($info);
				array_shift($info);
			} elseif (strtolower($info[0]) == 'null') {
				$null = "NULL";
			}
			
			if (count($info)) {
				if (strtolower($info[0]) == 'default') {
					array_shift($info);
					$default = "DEFAULT ".array_shift($info);
				}
			}
			
			if (count($info)) {
				$extra = implode(' ',$info);
			}
		}
		
		foreach (do_list($table) as $table) {
		
			if (!column_exists($table,$col)) {
				
				if ($prev_table != $table) { 
					
					$prev_col = '';
				}
				
				if (is_null($after) and $prev_col) {
					
					$position = "AFTER `$prev_col`";
				
				} elseif ($after == 'FIRST') {
				
					$position = "FIRST";
				
				} elseif ($after and column_exists($table,$after)) {
					
					$position = "AFTER `$after`";
				
				} else {
					
					$position = "";
				}
				
				$alter = "ADD COLUMN `$col` $type $null $default $extra $position"; 
				$alter = preg_replace('/\s\s+/',' ',$alter);
				
				if (safe_alter($table,$alter,$debug)) {
					
					$prev_col = $col;
					
					$count += 1;
				}
			}
			
			$prev_table = $table;
		}
		
		return $count;
	}
	
// -------------------------------------------------------------
	function safe_rename($table, $new_table, $debug=1)
	{
		if (table_exists($table)) {
			
			if (!table_exists($new_table)) { 
				
				$old = safe_pfx($table);
				$new = safe_pfx($new_table);
		
				if (safe_query("RENAME TABLE $old TO $new",$debug)) {
				
					return true;
				}
			}
		}
		
		return false;
	}
	
// -------------------------------------------------------------
	function safe_field($thing, $table, $where='1=1', $debug='',$pfx=null)
	{
		$q = "SELECT $thing FROM ".safe_pfx_j($table,$pfx)." WHERE $where";
		
		$r = safe_query($q,$debug);
		if (@mysql_num_rows($r) > 0) {
			$f = mysql_result($r,0);
			mysql_free_result($r);
			return $f;
		}
		return false;
	}

// -------------------------------------------------------------
// change: when 2 columns are given, use the first one for the array index
// otherwise use the first column for both the array key and value 
// change: sortcol attribute for seperate non-returning sorting columns 

	function safe_column($thing, $table, $where, $sortcol='',$debug='') 
	{
		$thing = do_list($thing);
		$count = count($thing);
		
		if ($sortcol and !in_array($sortcol,$thing)) { 
			
			$thing[] = $sortcol;
		}
		
		$thing = implode(',',$thing);
		
		$q = "SELECT $thing FROM ".safe_pfx_j($table)." WHERE $where";
		$rs = getRows($q,$debug);
		
		if ($rs) {
			foreach($rs as $a) {
				
				$k = array_shift($a);
				
				if ($count == 1) 
					$out[$k] = $k;
				elseif ($count == 2)
					$out[$k] = array_shift($a);
				else
					$out[$k] = $a;
			}
			
			return $out;
		}
		return array();
	}

// -------------------------------------------------------------
/*	function safe_column($thing, $table, $where, $debug='')
	{
		$q = "select $thing from ".safe_pfx_j($table)." where $where";
		$rs = getRows($q,$debug);
		if ($rs) {
			foreach($rs as $a) {
				$v = array_shift($a);
				$out[$v] = $v;
			}
			return $out;
		}
		return array();
	}
*/
// -------------------------------------------------------------
	function safe_row($things, $table, $where, $prefix='', $debug='')
	{
		if (is_array($where)) {
			
			foreach ($where as $name => $value) {
				$where[$name] = str_pad('`'.trim($name).'`',15).' = '.doQuote($value);
			}
			
			$where = n.implode(' AND '.n,$where);
		}
		
		$q = "SELECT $things FROM ".safe_pfx_j($table)." WHERE $where";
		$rs = getRow($q,$debug);
		
		if ($rs) {
			
			return ($prefix) ? add_var_pfx($rs,$prefix) : $rs;
		}
		return array();
	}

// -------------------------------------------------------------
	function safe_rows($things, $table, $where='1=1', $prefix='', $debug='')
	{	
		if (is_array($things)) {
			$things = n.implode(','.t.n,$things).n;
		}
		
		if (is_array($where)) {
			
			foreach ($where as $name => $value) {
				$where[$name] = str_pad('`'.trim($name).'`',15).' = '.doQuote($value);
			}
			
			$where = n.implode(' AND '.n,$where);
		}
	
		$q = "SELECT $things FROM ".safe_pfx_j($table)." WHERE $where";
		$rs = getRows($q,$debug);
		
		if ($rs) {
			
			return ($prefix) ? add_var_pfx($rs,$prefix) : $rs;
		}
		
		return array();
	}

// -------------------------------------------------------------
	function safe_rows_start($things, $table, $where, $debug='')
	{	
		$q = "SELECT $things FROM ".safe_pfx_j($table)." WHERE $where";
		return startRows($q,$debug);
	}

//-------------------------------------------------------------
	function safe_count($table, $where='1=1', $debug='',$pfx=null)
	{
		return getThing("SELECT COUNT(*) FROM ".safe_pfx_j($table,$pfx)." WHERE $where",$debug);
	}

// -------------------------------------------------------------
	function safe_show($thing, $table, $debug='')
	{
		$q = "SHOW $thing FROM ".safe_pfx($table)."";
		$rs = getRows($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}

//-------------------------------------------------------------
	function fetch($col,$table,$key=1,$val=1,$debug='')
	{
		$key = (is_int($key)) ? $key : "`".doSlash($key)."`";
		$val = (is_int($val)) ? $val : "'".doSlash($val)."'";
		$q = "SELECT $col FROM ".safe_pfx($table)." WHERE $key = $val LIMIT 1";
		if ($r = safe_query($q,$debug)) {
			$thing = (mysql_num_rows($r) > 0) ? mysql_result($r,0) : '';
			mysql_free_result($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRow($query,$debug='')
	{
		if ($r = safe_query($query,$debug)) {
			$row = (mysql_num_rows($r) > 0) ? mysql_fetch_assoc($r) : false;
			mysql_free_result($r);
			return $row;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRows($query,$debug='')
	{
		if ($r = safe_query($query,$debug)) {
			if (mysql_num_rows($r) > 0) {
				while ($a = mysql_fetch_assoc($r)) $out[] = $a;
				mysql_free_result($r);
				return $out;
			}
		}
		return false;
	}

//-------------------------------------------------------------
	function getColumns($table,$pfx=null,$incl='')
	{
		static $prev_table = '';
		static $columns = array();
		
		if ($prev_table != $table) {
			
			$columns = getThings('describe '.safe_pfx($table,$pfx));
			
			if ($incl and $columns) {
				
				$incl  = do_list($incl);
				$include = array(); 
				
				foreach($columns as $key => $name) {
					
					foreach($incl as $in) {
						
						if (str_ends_with($in,'*')) {
							
							if (str_begins_with($name,rtrim($in,'*'))) {
								$include[] = $name;
							}
							
						} else {
							
							if ($name == $in) {
								$include[] = $name;
							}
						}
					}
				}
				
				$columns = $include;
			}
		}
		
		return $columns;
	}

//-------------------------------------------------------------
	function getColumnInfo($table,$col='',$pfx=null)
	{
		if ($col) {
		
			if (column_exists($table,$col)) {
				
				$after = '';
				$info  = array();
				
				$rows = getRows('describe '.safe_pfx($table,$pfx));
				
				foreach ($rows as $key => $row) {
					
					if ($row['Field'] == $col) {
						
						foreach ($row as $name => $value) {
							
							$name = strtolower($name);
							
							if ($name == 'null') {
								$value = ($value == 'NO') ? 'NOT NULL' : 'NULL';
							}
							
							$info[$name] = $value;
						}
						
						$info['after'] = $after;
						$info['first'] = ($key == 0) ? 1 : 0;
						
						return $info;
					}
					
					$after = $row['Field'];
				}
			}
			
		} else {
			
			if (table_exists($table)) {
				
				return getRows('describe '.safe_pfx($table,$pfx));
			}
		}
		
		return false;
	}
	
//-------------------------------------------------------------
	function startRows($query,$debug='')
	{
		return safe_query($query,$debug);
	}

//-------------------------------------------------------------
	function nextRow($r)
	{
		$row = mysql_fetch_assoc($r);
		if ($row === false)
			mysql_free_result($r);
		return $row;
	}

//-------------------------------------------------------------
	function numRows($r)
	{
		return mysql_num_rows($r);
	}

//-------------------------------------------------------------
	function getThing($query,$debug='')
	{
		if ($r = safe_query($query,$debug)) {
			$thing = (mysql_num_rows($r) != 0) ? mysql_result($r,0) : '';
			mysql_free_result($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function getThings($query,$debug='')
	// return values of one column from multiple rows in an num indexed array
	{
		$rs = getRows($query,$debug); 
		if ($rs) {
			foreach($rs as $a) $out[] = array_shift($a);
			return $out;
		}
		
		return array();
	}

//-------------------------------------------------------------
	function getCount($table,$where='1=1',$debug='')
	{
		return getThing("SELECT COUNT(*) FROM ".safe_pfx_j($table)." WHERE $where",$debug);
	}

//==============================================================================
	function safe_update_tree($root,$table,$set,$where='1=1',$debug=0)
 	{ 
 		if (empty($set)) return;
 		
 		$where = ($where) ? array($where) : array();
 		
 		if (!is_numeric($root)) {
			$root = safe_field("ID",$table,$root);
		}
		
		if (!$root) return;
		
		$row = safe_row("Children,Path,Level",$table,"ID = $root");
		
		if ($row and $row['Children'] != 0) {
			
			if ($row['Children'] == -1) {
				$row['Children'] = safe_count($table,"ParentID = $root");
			}
			
			if ($row['Children']) {
			
				$path = $row['Path'];
				$maxlevel = fetch("MAX(Level)",$table);
				$path = ($path) ? explode('/',$path) : array();
				if (($row['Level']) > 1) array_push($path,$root);
				
				if (count($path) < $maxlevel) {
				
					if (count($path)) {
					
						foreach($path as $key => $value) {
							$where[] = "P".($key+2)." = $value";
						}
					}
					
					if ($where) safe_update($table,$set,doAnd($where),$debug);
				}
			}
		}
 	}

// -------------------------------------------------------------
	function safe_field_tree($root, $thing, $table, $where='1=1', $debug='', $trash=0) 
	{		
		$where = ($where) ? array($where) : array();
		
		if (!is_numeric($root)) {
			$root = safe_field("ID",$table,$root);
		}
		
		if (!$root) return '';
		
		$row = safe_row("Children,Path,Level",$table,"ID = $root");
		
		if ($row and $row['Children'] != 0) { 
			
			if ($row['Children'] == -1) {
				$row['Children'] = safe_count($table,"ParentID = $root");
			}
			
			if ($row['Children']) {
			
				$path = $row['Path'];
				$maxlevel = fetch("MAX(Level)",$table);
				$path = ($path) ? explode('/',$path) : array();
				if (($row['Level']) > 1) array_push($path,$root);
				
				if (count($path) < $maxlevel) {
				
					if (count($path)) {
					
						foreach($path as $key => $value) {
							$where[] = "P".($key+2)." = $value";
						}
					}
					
					if (!$trash) $where[] = "Trash = 0";
					
					return safe_field($thing,$table,doAnd($where),$debug);
				}
			}
		}
		
		return '';
	}

// -------------------------------------------------------------
	function safe_rows_tree($root, $things, $table, $where='1=1', $debug='', $trash=0) 
	{
		$where = ($where) ? array($where) : array();
		
		$as = 't';
		
		if (preg_match('/JOIN/',$table)) {
			
			$table1 = explode(' JOIN ',$table);
			
			$as = explode(' AS ',$table1[0]);
			$as = array_pop($as);
			
			$table1 = array_shift($table1);
			
		} else {
			
			$table1 = $table;
			$table .= ' AS '.$as;
		}
		
		if (!$trash) $where[] = "$as.Trash = 0";
		$orderby = " ORDER BY $as.lft ASC";
		
		if (!is_array($root)) {
			
			if (!$root) {
			
				$where[] = "$as.ParentID != 0";
				$where[] = "$as.Name != 'TRASH'";
				
				return safe_rows($things,$table,doAnd($where).$orderby,0,$debug);
			}
			
			$root = (!is_numeric($root)) 
				? safe_column("ID",$table1,$root) 
				: array($root);
		}
		
		$out = array();
		$maxlevel = fetch("MAX(Level)",$table1);
		
		foreach ($root as $id) {
			
			$row = safe_row("Children,Path,Level",$table1,"ID = '$id'",'',$debug);
			
			
			if ($row and $row['Children'] != 0) { 
			
				if ($row['Children'] == -1) {
					$row['Children'] = safe_count($table1,"ParentID = $id");
				}
				
				if ($row['Children']) {
			
					$path = $row['Path'];
					$path = ($path) ? explode('/',$path) : array();
					if (($row['Level']) > 1) array_push($path,$id);
					
					if (count($path) < $maxlevel) {
						
						if (count($path)) {
						
							foreach($path as $key => $value) {
								$where['p'][] = $as.".P".($key+2)." = $value";
							}
							
							$where['p'] = implode(' AND ',$where['p']);
						}
						
						$out = array_merge($out,safe_rows($things,$table,doAnd($where).$orderby,'',$debug));
					}
				}
			}
			
			$where['p'] = array();
		}
		
		return $out;
	}
	
//-------------------------------------------------------------
	function safe_count_tree($root, $table, $where='1=1', $debug=0)
	{	
		$where = (is_array($where)) ? $where : array($where);
		
		$as = 't';
		
		if (preg_match('/ JOIN /',$table)) {
			
			$table1 = explode(' JOIN ',$table);
			
			$as = explode(' AS ',$table1[0]);
			$as = array_pop($as);
			
			$table1 = array_shift($table1);
			
		} else {
			
			$table1 = $table;
			$table .= ' AS '.$as;
		}
		
		if (!is_numeric($root)) {
			$root = safe_field("ID",$table1,$root);
		}
		
		if (!$root) return array();
		
		$row = safe_row("Children,Path,Level",$table1,"ID = $root");
		
		if ($row and $row['Children'] != 0) { 
		
			if ($row['Children'] == -1) {
				$row['Children'] = safe_count($table1,"ParentID = $root");
			}
				
			if ($row['Children']) {
				
				if ($root != fetch("ID",$table1,"ParentID",0)) {
					
					$path = $path = $row['Path'];
					$maxlevel = fetch("MAX(Level)",$table1);
					$path = ($path) ? explode('/',$path) : array();
					if (($row['Level']) > 1) array_push($path,$root);
					
					if (count($path) < $maxlevel) {
					
						if (count($path)) {
						
							foreach($path as $key => $value) {
								$where[] = $as.".P".($key+2)." = $value";
							}
						}
					}
					
				} else {
				
					$where[] = "$as.ID != $root AND $as.Name != 'TRASH'";
				}
				
				return ($where) ? safe_count($table,doAnd($where),$debug) : 0;
			}
		}
		
		return array();
	}

// ---------------------------------------------------------
// update summary field
	
	function update_summary_field($table,$field,$debug=0) 
	{
		global $PFX;
		
		$maxlevel = fetch("MAX(Level)",$table);
		
		for ($level = $maxlevel; $level > 1; $level--) {
			
			$child = n."(SELECT ParentID, SUM($field) AS $field
				FROM ".$PFX.$table."
				WHERE Level = $level AND Trash = 0 AND Name != 'TRASH'
				GROUP BY ParentID)".n;
			
			safe_update("$table AS parent JOIN $child AS child ON parent.ID = child.ParentID".n,
				"parent.$field = child.$field".n,
				"parent.Level = ($level - 1) AND parent.Trash = 0 AND parent.Name != 'TRASH'",$debug);
		}
	}


//==============================================================================
	function table_exists($table,$pfx=null) 
	{	
		global $tables;
		
		if (!is_null($pfx) or !$tables) {
			
			// $table = safe_pfx($table,$pfx);
			
			return in_array($table,safe_tables('',$pfx));
		}
		
		return in_array($table,$tables);
	}

//-------------------------------------------------------------	
	function get_prefixes($db) {
	
		$prefixes = safe_column("Prefix","txp_site",
			"DB = '$db' AND SiteDir != ''");
			
		foreach($prefixes as $key => $pfx) {
			$pfx .= ($pfx) ? '_' : ''; 
			if (table_exists($pfx.'txp_image')) {
				$prefixes[$key] = $pfx;
			} else {
				unset($prefixes[$key]);	
			}
		}
		
		return $prefixes;
	}
		
//-------------------------------------------------------------
	function table_prefix_exists($prefix) 
	{	
		global $tables;
		
		foreach($tables as $table) {
			
			if (str_starts_with($table,$prefix)) return true;
		}
		
		return false;
	}
	
//-------------------------------------------------------------
	function column_exists($table,$name,$pfx=null) 
	{	
		$count = array();
		
		if (!strlen($name)) return 0;
		
		foreach(do_list($table) as $key => $table) { 
			
			$count[$key] = 1;
			
			if (strlen($name) and table_exists($table,$pfx)) {
				
				$names = explode(',',$name);
				
				$columns = getColumns($table,$pfx);
				$columns = doArray($columns,'strtolower');
				
				foreach ($names as $name) {
					
					$name = trim(strtolower($name));
					
					if (substr($name,0,1) == '!') {
						if (in_array(ltrim($name,'!'),$columns)) $count[$key] = 0;
					} else {
						if (!in_array($name,$columns)) $count[$key] = 0;
					}
				}
			
			} else {
				
				$count[$key] = 0;
			}
		}
		
		return array_sum($count); 
	}

//-------------------------------------------------------------
	function pref_exists($name,$value=null) 
	{	
		$where  = "name = '$name'";
		$where .= (!is_null($value)) ? " AND val = '$value'" : "";
				
		return safe_count('txp_prefs',$where);
	}

//-------------------------------------------------------------
	function index_exists($table,$name,$pfx=null) 
	{	
		if (table_exists($table)) {
			
			$table = safe_pfx($table,$pfx);
		
			$rows = safe_query("SHOW INDEX FROM $table WHERE Key_name = '$name'");
		
			return ($rows and nextRow($rows));
		}
		
		return false;
	}

//-------------------------------------------------------------
	function sql_comment($text,$pad=0) 
	{	
		return " /* ".str_pad($text,$pad)." */";
	}

//-------------------------------------------------------------
	function explain($q) 
	{	
		global $DB;
		
		$result = mysql_query("EXPLAIN $q",$DB->link);
		
		if (mysql_num_rows($result) > 0) {
			
			while ($a = mysql_fetch_assoc($result)) 
				$out[] = $a;
			
			mysql_free_result($result);

			// - - - - - - - - - - - - - - - - - - - - - - - 
			
			$table['open'] = '<table class="explain">';
			
			// - - - - - - - - - - - - - - - - - - - - - - - 
			
			$table['head'] = "<tr>\n";
			
			foreach(array_keys($out[0]) as $key) {
				$table['head'] .= "\t<th class=\"$key\">".str_replace('_',' ',$key)."</th>\n";
			}
			
			$table['head'] .= "</tr>";

			// - - - - - - - - - - - - - - - - - - - - - - - 
			
			foreach($out as $key => $row) {
				
				$table[$key] = "<tr>\n";
				
				foreach($row as $class => $value) {
					$table[$key] .= "\t<td class=\"$class\">".str_replace(',',', ',$value)."</td>\n";
				}
			
				$table[$key] .= "</tr>";
			}

			// - - - - - - - - - - - - - - - - - - - - - - - 
			
			$table['close'] = '</table>';

			// - - - - - - - - - - - - - - - - - - - - - - - 
			
			return implode(n,$table);
		}
	}

//-------------------------------------------------------------
// change: added $name attribute to return only named preference if given

	function get_prefs($name='')
	{
		global $prefs, $txp_user;
		
		$out = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if (trim($name) and $prefs) {
			
			$names = (!is_array($name)) ? explode(',',trim($name)) : $name;
			
			foreach ($names as $name) {
				
				$name = trim($name);
				
				$out[$name] = (isset($prefs[$name])) ? $prefs[$name] : '';
			}
			
			return $out;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if ($name) $name = " AND name = '$name'";
		
		$user_name = '';
		
		if (column_exists('txp_prefs','user_name')) {
		
			$user_name = ($txp_user) ? "AND user_name='".doSlash($txp_user)."'" : '';
		}
		
		// get current user's private prefs
		if ($txp_user) {
			$r = safe_rows_start('name, val', 'txp_prefs', 'prefs_id = 1 '.$name.$user_name);
			if ($r) {
				while ($a = nextRow($r)) {
					$out[$a['name']] = $a['val'];
				}
			}
		}
		
		$user_name = '';
		
		if (column_exists('txp_prefs','user_name')) {
		
			$user_name = ($txp_user) ? "AND user_name=''" : '';
		}
		
		// get global prefs, eventually override equally named user prefs.
		$r = safe_rows_start('name, val', 'txp_prefs', 'prefs_id = 1 '.$name.$user_name);
		if ($r) {
			while ($a = nextRow($r)) {
				$out[$a['name']] = $a['val'];
			}
		}
		return $out;
	}
	
// -------------------------------------------------------------
	function db_down()
	{
		// 503 status might discourage search engines from indexing or caching the error message
		txp_status_header('503 Service Unavailable');
		$error = mysql_error();
		return <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Untitled</title>
</head>
<body>
<p align="center" style="margin-top:4em">Database unavailable.</p>
<!-- $error -->
</body>
</html>
eod;
	}

// =============================================================================
	function getTree($root, $type, $where='1=1', $tbl='txp_category')
	{

		$root = doSlash($root);
		$type = doSlash($type);

		$rs = safe_row(
			"lft as l, rgt as r",
			$tbl,
			"name='$root' and type = '$type'"
		);

		if (!$rs) return array();
		extract($rs);

		$out = array();
		$right = array();

		$rs = safe_rows_start(
			"id, name, lft, rgt, parent, title",
			$tbl,
			"lft between $l and $r and type = '$type' and name != 'root' and $where order by lft asc"
		);

		while ($rs and $row = nextRow($rs)) {
			extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) {
				array_pop($right);
			}

			$out[] =
				array(
					'id' => $id,
					'name' => $name,
					'title' => $title,
					'level' => count($right),
					'children' => ($rgt - $lft - 1) / 2,
					'parent' => $parent
				);

			$right[] = $rgt;
		}
		return($out);
	}

// -------------------------------------------------------------
	function getTreePath($target, $type, $tbl='txp_category')
	{
		$rs = safe_row(
			"lft as l, rgt as r",
			$tbl,
			"name='".doSlash($target)."' and type = '".doSlash($type)."'"
		);
		if (!$rs) return array();
		extract($rs);

		$rs = safe_rows_start(
			"*",
			$tbl,
				"lft <= $l and rgt >= $r and type = '".doSlash($type)."' order by lft asc"
		);

		$out = array();
		$right = array();

		while ($rs and $row = nextRow($rs)) {
			extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) {
				array_pop($right);
			}

			$out[] =
				array(
					'id' => $id,
					'name' => $name,
					'title' => $title,
					'level' => count($right),
					'children' => ($rgt - $lft - 1) / 2
				);

			$right[] = $rgt;
		}
		return $out;
	}

// -------------------------------------------------------------
// change: $type attribute optional
// change: $col attribute to specify which column to use for parent 

	function rebuild_tree($parent='root', $left=1, $type='', $tbl='txp_category',$col='name')
	{
		$left  = assert_int($left);
		$right = $left+1;

		$parent = doSlash($parent);
		$type   = doSlash($type);
		$istype = ($type) ? "type = '$type'" : '1';

		$result = safe_column($col, $tbl,
			"parent='$parent' AND $istype ORDER BY $col",'');

		foreach($result as $row) {
			$right = rebuild_tree($row, $right, $type, $tbl, $col);
		}

		safe_update(
			$tbl,
			"lft=$left, rgt=$right",
			"$col='$parent' AND $istype"
		);
		return $right+1;
	}

//-------------------------------------------------------------
	function rebuild_tree_full($type, $tbl='txp_category')
	{
		# fix circular references, otherwise rebuild_tree() could get stuck in a loop
		safe_update($tbl, "parent=''", "type='".doSlash($type)."' AND name='root'");
		safe_update($tbl, "parent='root'", "type='".doSlash($type)."' AND parent=name");

		rebuild_tree('root', 1, $type, $tbl);
	}

//-------------------------------------------------------------
	function rebuild_txp_tree($parent=0, $left=0, $table='') 
	{	
		global $WIN;
		
		$textpattern = (!$table) ? $WIN['table'] : $table;
		
		if ($parent and $left == 0) {
			$left = fetch("lft",$textpattern,"ID",$parent);
		}
		
		$right = $left + 1;
		
		$result = safe_column("ID",$textpattern,"ParentID = $parent");
		
		foreach($result as $id) { 
			$right = rebuild_txp_tree($id, $right, $textpattern);
		}
		
		safe_update($textpattern,"lft = $left, rgt = $right","ID = $parent",0);
		
		return $right + 1;
	}

//-------------------------------------------------------------
	function get_path($id,$table='',$content='',$debug=0) 
	{	
		global $WIN;
		
		$table   = (!$table)   ? $WIN['table']   : $table;
		$content = (!$content) ? $WIN['content'] : $content;
		
		if ($id == fetch("ID",$table,"ParentID",0)) {
		
			$path = array("t.ID != $id");
		
		} else {
		
			$path = fetch('Path',$table,"ID",$id,$debug);
			
			$path = (strlen($path)) ? explode('/',$path) : array();
			$path[] = $id;
			
			foreach ($path as $key => $value) {
				$path[$key] = "t.P".($key+2)." = $value";
			}
		}
		
		return implode(' AND ',$path);
	}
	
	
//-------------------------------------------------------------
	function update_path($ids=0,$tree=0,$table='',$content='',$debug=0) 
	{	
		global $PFX, $WIN;
		
		$tree = ($tree == 'TREE') ? 1 : $tree;
		$tree = ($tree == 'SELF') ? 0 : $tree;
		
		$table   = (!$table)   ? $WIN['table']   : $table;
		$content = (!$content) ? $WIN['content'] : $content;
		
		$last_path_col = count_path_columns($table) + 1;
		$levels  = 0;
		
		$tmp = $WIN['table'];
		$WIN['table'] = $table;
		
		if (!$ids) {
			
			// zero out all Path,Level,P# columns in textpattern table
			
			$set = array("Path = '', Level = 0, Children = -1");
			
			for ($i = 2; $i <= $last_path_col; $i++) {
				$set[] = "P$i = NULL";
			}
			
			safe_update($table,impl($set),"1=1",0);
			
			$ids = array(0);
		
		} else {
			
			$ids = (!is_array($ids)) ? explode(',',$ids) : $ids;
			$root_node_id = fetch("ID",$table,"ParentID",0);
		}
		
		if ($debug == 3) getmicrotime('update_path_columns');
		
		$update = array();
		
		foreach ($ids as $id) {
			
			$path = array();
			
			if ($id) { 
				
				$parent_id = fetch("ParentID",$table,"ID",$id);
				
				if ($parent_id != $root_node_id) {
					$path  = fetch("Path",$table,"ID",$parent_id);
					$path .= ($path) ? '/'.$parent_id : $parent_id;
					$path  = ($path) ? explode('/',$path) : array();
				}
			}
			
			$update = $update + update_path_columns($id,$tree,"$table,$content",null,$path,$debug);
		}
		
		if ($debug == 3) {
			$time = getmicrotime('update_path_columns');
			pre("Get path values: $time seconds");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// values for textpattern table
		
		foreach ($update as $id => $item) {
			
			unset($item['Children']);
			
			$key = ($item) ? $item['Level'].'/'.$item['Path'] : 0;
			
			foreach($item as $name => $value) {
				
				$item[$name] = ($value !== 'NULL') 
					? "$name = '$value'" 
					: "$name = NULL";
				
				if ($name == 'Level' and $value > $levels) {
					$levels = $value;
				}
			}
			
			$update[$key]['set'] = implode(',',$item);
			$update[$key]['ids'][] = $id;
			 
			unset($update[$id]);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add extra path columns if needed
		
		if (($last_path_col + 1) < $levels) {
			
			add_path_columns($table,$last_path_col,$levels);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// update textpattern table 
		
		if ($levels) {
			
			$update_count = 0;
			$row_count = 0;
			
			if ($debug) getmicrotime('update_path_update');
			
			foreach ($update as $key => $item) {
				
				$ids = implode(',',$item['ids']);
				
				if ($item['set']) {
					
					safe_update($table,$item['set'],"ID IN ($ids)",$debug);
					
					if ($debug) {
						$update_count += 1;
						$row_count += count($item['ids']);
					}
				}
			}
			
			if ($debug) {
				$time = getmicrotime('update_path_update');
				pre("$time seconds for $update_count updates to $row_count rows in $table table");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// indexes
		
		add_path_indexes($table,$debug);
		
		$WIN['table'] = $tmp;
	}

//-------------------------------------------------------------
	function update_path_columns($id,$tree,$table,$parent,$path,$debug) 
	{
		list($table,$content) = expl($table);
		
		static $update  = array();
		static $root_node_id = 0;
		
		if ($id == 0 or $tree <= 1) {
			
			$update = array();
			
			$root_node_id = fetch("ID",$table,"ParentID",0);
		}
		
		$levels = count_path_columns($table) + 2;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$select = array(
			'ID','ParentID','Name','Class','Status','Position','Posted','Trash','Path','Children'
		);
		
		$where = ($id == 0 or $tree > 1) 
			? "t.ParentID = $id"
			: "t.ID = $id";
		
		$rows = safe_rows(impl($select),"$table AS t",$where,0,0);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$update[$id]['Children'] = 0;
		
		if ($id and $id != $root_node_id and $tree > 1) {
			$path[] = $id;
		}
		
		foreach ($rows as $key => $row) {
			
			$ID	   	  = $row['ID']; 
			$ParentID = $row['ParentID'];
			$Trash    = $row['Trash'];
			$Name     = $row['Name'];
			$Children = intval($row['Children']);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$level = ($ParentID) ? count($path) + 2 : 1;
		    
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($tree <= 1) {
				
				$update[$ID]['Children'] = safe_count($table,"ParentID = $ID AND Trash = 0 AND Name != 'TRASH'");
				
			} else {
				
				if ($Children != -1) {
					$update[$ID]['Children'] = $Children;
				} else {
					$update[$ParentID]['Children'] += (!$Trash and $Name != 'TRASH') ? 1 : 0;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$update[$ID]['Level'] = $level;
			$update[$ID]['Path']  = "NULL";
			
			if ($level > 2) {
				$update[$ID]['Path']  = implode('/',$path);
			}
			
			$key = 2;
				
			foreach ($path as $value) {
				
				$update[$ID]['P'.$key] = $value;
				
				$key++;
			}
			
			for ($key = $key; $key < $levels; $key++) {
			
				$update[$ID]['P'.$key] = 'NULL';
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($content == 'article') {
				
				if ($tree > 1) {
					
					$update[$ID]['ParentName']     = $parent['Name'];
					$update[$ID]['ParentStatus']   = $parent['Status'];
					$update[$ID]['ParentPosition'] = $parent['Position'];
					$update[$ID]['ParentClass']    = $parent['Class'];
					$update[$ID]['ParentPosted']   = $parent['Posted'];
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($tree and $Children != 0) { 
				
				update_path_columns($ID,$tree+1,"$table,$content",$row,$path,$debug);
			}
		}
	
		
		return $update;
	}

//-------------------------------------------------------------
	function add_path_columns($table,$start,$end,$p=array(),$debug=0) 
	{	
		for ($i = 2; $i < $end; $i++) {
			
			if ($i > $start) {
			
				if (!column_exists($table,"P$i")) {
							
					safe_alter($table,"ADD COLUMN `P$i` int NULL default NULL",$debug);
				}
			}
		}
	}

//-------------------------------------------------------------
// update the parent info of the children for given ID 

	function update_parent_info($table,$id=0,$debug=0) 
	{
		if (column_exists($table,'ParentName')) {
				
			if ($debug) pre("Update Parent Info");
			
			$parent = array('Status','Position','Name','Class','Posted');	
			
			foreach($parent as $key => $column) {
			
				$parent[$key] = n.t."t.Parent".$column." = p.".$column;
			}
			
			// $where = ($id) ? "t.ID = $id" : "1=1";
			$where = ($id) ? "t.ParentID = $id" : "1=1";
			
			safe_update("$table AS t JOIN $table AS p ON t.ParentID = p.ID",impl($parent),$where,$debug);
			safe_update($table,"ParentStatus = Status","ParentID = 0",$debug); 
		}
	}
				
//-------------------------------------------------------------
	function count_path_columns($table) 
	{
		global $PFX;
		
		$columns = getThings('describe '.$PFX.$table);
		
		$count = 0;
				
		foreach ($columns as $key => $value) {
			
			if (preg_match('/^P\d+$/',$value)) $count++;
		}
		
		return $count;
	}

//-------------------------------------------------------------
	function trim_path_columns($table,$start=0,$debug=0) 
	{
		$level = count_path_columns($table) + 2;
		$drop  = false;
				
		for ($i = $start; $i < $level; $i++) {
			
			if ($i > 2 or $start == 1) {	
				safe_drop($table,"P$i",$debug);
				$drop = true;
			}
		}
		
		if ($drop) {
		
			drop_path_indexes($table,$debug);
		}
	}

//-------------------------------------------------------------
	function add_path_indexes($table,$debug=0) 
	{
		$count = count_path_columns($table) + 1;
		$path  = array();
		
		for ($i = 2; $i <= $count; $i++) {
		
			$path[] = "P$i";
		}
		
		safe_index($table,"Path",implode(',',$path),$debug);	
		
		array_unshift($path,'Level');
		
		if ($table == 'textpattern') {
		
			$columns = array('Position','Posted');
		
			$path = array_slice($path,0,13);
			
			foreach($columns as $key => $col) {
			
				if ($key > 0) array_pop($path);
				
				array_push($path,$col);
				
				safe_index($table,"Path_Level_".$col,implode(',',$path),$debug);	
			}
		}
		
		if ($table == 'txp_path') {
			
			safe_index($table,"Path_Level",implode(',',$path),$debug);	
		}
	}

//-------------------------------------------------------------
	function drop_path_indexes($table,$debug=0) 
	{
		safe_unindex($table,"Path_Level_Position",$debug);
		safe_unindex($table,"Path_Level_Posted",$debug);
	}
	
// =============================================================================
	function retrieve_session_data($winid=0) 
	{
		global $txp_user;
		
		if ($session = fetch("session","txp_users","name",$txp_user)) {
			
			$session = unserialize(base64_decode($session));
			
			if (table_exists("txp_window")) {
						
				$settings = safe_field("settings","txp_window","user = '$txp_user' AND window = '$winid'");
				
				if ($settings) {
					
					pre('retrieve_session_data() txp_window: '.$winid);
					
					$settings = unserialize(base64_decode($settings));
					
					if (!isset($session['window'])) {
						$session['window'] = array($winid => $settings);
					} else {
						$session['window'][$winid] = $settings;
					}
				}
			}
						
			$_SESSION = $session;
		}
	}

//-------------------------------------------------------------
	function store_session_data() 
	{
		global $WIN, $txp_user;
		
		$winid = $WIN['winid'];
		
		if (count($_SESSION)) {
			
			$session = $_SESSION;
			
			if (table_exists("txp_window")) {
				
				if (isset($session['window'][$winid])) {
				
					$settings = $session['window'][$winid];
					$settings = base64_encode(serialize($settings));
					
					if (safe_count("txp_window","user = '$txp_user' AND window = '$winid'")) {
						
						// pre('store_session_data() update txp_window: '.$winid);
						
						safe_update("txp_window",
							"settings = '$settings'",
							"user = '$txp_user' AND window = '$winid'");
					
					} else {
						
						// pre('store_session_data() insert txp_window: '.$winid);
						
						safe_insert("txp_window",
							"user = '$txp_user', 
							 window = '$winid',
							 settings = '$settings'");
					}
				}
				
				$session['window'] = array();
			}
			
			$session = base64_encode(serialize($session));
			
			safe_update("txp_users","session = '$session'","name = '$txp_user'");
		}
	}

//-------------------------------------------------------------
	function clear_session_data($allusers=0,$debug=0) 
	{
		global $txp_user;
		
		$where = ($allusers) ? '1' : "name = '$txp_user'";
		
		safe_update("txp_users","session = ''",$where,$debug);
		safe_delete("txp_window","1=1",$debug);
		
		if (isset($_SESSION)) {
			if (isset($_SESSION['event']))  unset($_SESSION['event']);
			if (isset($_SESSION['window'])) unset($_SESSION['window']);
			if (isset($_SESSION['list'])) 	unset($_SESSION['list']);
			if (isset($_SESSION['image'])) 	unset($_SESSION['image']);
			if (isset($_SESSION['file'])) 	unset($_SESSION['file']);
		}
	}

// =============================================================================
	function backup_db($now=0)
	{	
		global $DB, $PFX, $dump;
		
		$exclude = array(
			'txp_cache',
			'txp_log',
			'txp_lang',
			'txp_plugin',
			'txp_path',
			'txp_field',
			'txp_tag',
			'txp_tag_attr',
			'txp_window',
			'txp_update',
			'txp_section',
			'txp_discuss_ipban',
			'txp_discuss_nonce',
			'txp_log_cards',
			'txp_log_email',
			'txp_log_mention',
			'txp_log_page',
			'txp_log_time'
		);
		
		if ($path = get_db_backup_dir()) {
			
			$time = ($now) ? date("H-i") : '00-00';
			
			$filename = $DB->db.'-'.date("Y-m-d").'-'.$time;
			
			if (!is_file($path.$filename.'.gz')) {
				
				if (!is_file($path.$filename)) {
					
					$tables = safe_tables($exclude);
					
					// - - - - - - - - - - - - - - - - - - - - - - - - -
					// do php dump
					
					$data = '';
					
					foreach ($tables as $table) {
						
						$data .= '#TABLE:'.$table.n;
						
						$rows = safe_rows_start("*",$table,"1");
						
						while ($row = nextRow($rows)) {
						
							foreach ($row as $key => $val) {
								
								if (strlen($val) and !is_numeric($val)) {
									
									if (in_list($key,'Title,Title_html,Body,Body_html,Body_xsl,user_xsl,Excerpt,Excerpt_html,text_val')) {
										
										$val = doSlash(trim($val));
									
									} elseif (str_begins_with($key,'custom_')) {
										
										$val = doSlash(trim($val));
									}
								}
								
								$row[$key] = $val;
							}
							
							$data .= '#ROW:'.serialize($row).n;
						}
					}
					
					$error = write_to_file($path.$filename,$data,1);
					
					$dump[] = array($path.$filename.'.gz',$error);
					
					// - - - - - - - - - - - - - - - - - - - - - - - - -
					// do mysqldump
					
					foreach($tables as $key => $table) {
						$tables[$key] = $PFX.$table;
					}
					
					$cmdopt = implode(' ',array(
						"-h ".$DB->host,
						"-u ".$DB->user,
						'--password="'.$DB->pass."'",
						"--lock-tables=false",
						$DB->db,
						implode(' ',$tables)
					));
					
					system("mysqldump ".$cmdopt." > ".$path.$filename.'.sql');
					
					if (is_file($path.$filename.'.sql')) {
						system('gzip '.$path.$filename.'.sql');
						$dump[] = array($path.$filename.'.sql.gz');
					}
				}
			}
			
			// delete older than 4 week backups
			
			$filename = $DB->db.'-'.date("Y-m-d",strtotime("-4 week")).'-'.$time;
			
			if (is_file($path.$filename.'.gz')) {
				unlink($path.$filename.'.gz');
			}
			
			if (is_file($path.$filename.'.sql.gz')) {
				unlink($path.$filename.'.sql.gz');
			}
		}
	}

//-------------------------------------------------------------
	function restore_db($date='',$file='',$tables=array())
	{	
		global $DB;
		
		$inserted = 0;
		
		$path = get_db_backup_dir();
		
		if (!$path) return;
		
		$filename = ($date) ? $DB->db.'-'.$date : $file;
		
		process_file_by_line($path.$filename,'insert_from_file',$tables);
		
		return $tables;
	}

//-------------------------------------------------------------
	
	function insert_from_file($line,&$tables) 
	{	
		global $PFX;
		
		static $in_table = '';
		static $rowcount = 0;
		
		if (str_begins_with($line,'#TABLE:')) {
			
			$rowcount = 0;
			
			$table = substr(trim($line),7);
			
			if (isset($tables[$table])) {
			
				$tables[$table] = 0;
				
				$in_table = $table;
			
			} else {
				
				$in_table = '';
			}
			
		} elseif ($in_table and str_begins_with($line,'#ROW:')) {
			
			$rowcount += 1;
			
			if ($rowcount == 1) {
				safe_delete($in_table,'1');
			}
				
			$row = substr($line,5);
			
			// pre('INSERT ROW '.$row.' IN TABLE '.$in_table);
			
			$row = unserialize($row);
			$keys = array();
			$values = array();
			
			$test = array();
			
			if (is_array($row)) {
			
				foreach ($row as $key => $val) {
					
					if (strlen($val) and !is_numeric($val)) {
					
						if (in_list($key,'url_title,Name,ParentName')) {
							$val = make_name(trim($val));
						}
						
						if (str_begins_with($key,'custom_')) { 
							$val = doSlash(doStrip(trim($val)));
						}
					}
					
					$keys[]   = "`$key`";
					$values[] = "'$val'";
					$test[]   = "$key:'$val'";
				}
			
				// pre(impl($test));
				
				$result = safe_query("INSERT INTO ".$PFX.$in_table." (".impl($keys).") VALUES (".impl($values).")",0);
				
				if ($result !== false) {
				
					$tables[$in_table] += 1;
				}
			
			} else {
				
				echo('<div class="error">Error in table '.$in_table.'</div>');
				echo('<input type="text" style="white-space:no-wrap;border: 1px solid grey;width:90%;height:20px;" value="'.htmlentities($line).'"/>');
			}
		}
	}
	
//-------------------------------------------------------------
	function get_db_backup_dir() 
	{
		global $PFX, $path_to_site;
		
		if (is_dir($path_to_site.'/database')) {
			return $path_to_site.'/database/';
		}
		
		$path = explode('/',trim($path_to_site,'/'));
		
		
		if ($PFX and count($path) >= 3) {
			
			$path = array_reverse($path);
			
			if ($path[0] == 'public' and $path[2] == 'sites') {
				array_shift($path);
				array_unshift($path,'private');
			}
			
			$path = array_reverse($path);	
		}
		
		$path[] = 'database/';
		
		$path = '/'.implode('/',$path);
		
		if (!is_dir($path)) {
			mkdir($path,0777);
		}
		
		return (is_dir($path)) ? $path : '';
	}	

//-------------------------------------------------------------
	function mysqldump($file,$pfx=null)
	{
		global $DB,$PFX;
		/* 
		$pfx = (!$pfx) ? $PFX : $pfx;
		$pfx = ($pfx) ? rtrim($pfx,'_').'_' : '';
		
		$tables = safe_tables('',$pfx);
		*/
		
		$pfx = (is_null($pfx)) ? $PFX : $pfx;
		$pfx = ($pfx) ? rtrim($pfx,'_').'_' : '';
		
		$tables = safe_tables('',$pfx);
		
		$options = array(
			"-h ".$DB->host,
			"-u ".$DB->user,
			'--password="'.$DB->pass."'",
			"--lock-tables=false",
			"--default-character-set=utf8",
			"--skip-add-drop-table",
			"--skip-add-locks",
			"--skip-comments"
		);
		
		$compress = false;
		
		if (preg_match('/\.gz$/',$file)) {
			$compress = true;
			$file = preg_replace('/\.gz$/','',$file);
		}
		
		foreach ($tables as $key => $table) {
			$tables[$key] = $pfx.$table;
		}
		
		$tables  = implode(' ',$tables);
		$command = 'mysqldump '.implode(' ',$options).' '.$DB->db.' '.$tables.' > '.$file;
		
		system($command);
		
		if (is_file($file)) {
			
			if ($compress) system('gzip '.$file);
			
			return true;
		}
	}
?>
