<?php
	
	// Dealing with timezones.
	
	class timezone
	{
		/* private */
		var $_details;
		var $_offsets;

		/**
		 * Constructor
		 */
		function timezone()
		{
			// are we riding a dinosaur?
			if (!timezone::is_supported())
            {
            	// Standard time zones as compiled by H.M. Nautical Almanac Office, June 2004
	            // http://aa.usno.navy.mil/faq/docs/world_tzones.html
	            $timezones = array(
	                -12, -11, -10, -9.5, -9, -8.5, -8, -7, -6, -5, -4, -3.5, -3, -2, -1,
	                0,
	                +1, +2, +3, +3.5, +4, +4.5, +5, +5.5, +6, +6.5, +7, +8, +9, +9.5, +10, +10.5, +11, +11.5, +12, +13, +14,
	            );

	            foreach ($timezones as $tz)
	            {
	            	// Fake timezone id
	            	$timezone_id = 'GMT'.sprintf('%+05.1f', $tz);
	            	$sign = ($tz >= 0 ? '+' : '');
	                $label = sprintf("GMT %s%02d:%02d", $sign, $tz, abs($tz - (int)$tz) * 60);
	                $this->_details[$timezone_id]['continent'] = gTxt('timezone_gmt');
	                $this->_details[$timezone_id]['city'] = $label;
	                $this->_details[$timezone_id]['offset'] = $tz * 3600;
	                $this->_offsets[$tz * 3600] = $timezone_id;
	            }
            }
            else
            {
				$continents = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia',
					'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');
				
				$invalids = array(
					'America/Bahia_Banderas',
					'America/Matamoros',
					'America/Ojinaga',
					'America/Santa_Isabel',
					'Antarctica/Macquarie',
					'Asia/Kathmandu',
					'Asia/Novokuznetsk',
					'Pacific/Chuuk',
					'Pacific/Pohnpei'
				);

				$server_tz = date_default_timezone_get();
				$tzlist = timezone_abbreviations_list();
				foreach ($tzlist as $abbr => $timezones)
				{
					foreach ($timezones as $tz)
					{
						$timezone_id = $tz['timezone_id'];
						
						if (!in_array($timezone_id,$invalids)) {
							
							// $timezone_ids are not unique among abbreviations
							if ($timezone_id && !isset($this->_details[$timezone_id]))
							{
								$parts = explode('/', $timezone_id);
								if (in_array($parts[0], $continents))
								{
									if (!empty($server_tz))
									{
										if (date_default_timezone_set($timezone_id))
										{
											$is_dst = date('I', time());
										}
									}
	
									$this->_details[$timezone_id]['continent'] = $parts[0];
									$this->_details[$timezone_id]['city'] = (isset($parts[1])) ? $parts[1] : '';
									$this->_details[$timezone_id]['subcity'] = (isset($parts[2])) ? $parts[2] : '';
									$this->_details[$timezone_id]['offset'] = date_offset_get(date_create()) - ($is_dst ? 3600 : 0);
									$this->_details[$timezone_id]['dst'] = $tz['dst'];
									$this->_details[$timezone_id]['abbr'] = strtoupper($abbr);
	
									// Guesstimate a timezone key for a given GMT offset
									$this->_offsets[$tz['offset']] = $timezone_id;
								}
							}
						}
					}
				}
			}

			if (!empty($server_tz))
			{
				date_default_timezone_set($server_tz);
			}
		}

		/**
		 * Render HTML SELECT element for choosing a timezone
		 * @param	string	$name	Element name
		 * @param	string	$value	Selected timezone
		 * @param	boolean	$blank_first Add empty first option
		 * @param	boolean|string	$onchange n/a
		 * @param	string	$select_id	HTML id attribute
		 * @return	string	HTML markup
		 */
		function selectInput($name = '', $value = '', $blank_first = '', $onchange = '', $select_id = '')
		{
			if (!empty($this->_details))
			{
				$thiscontinent = '';
				$selected = false;

				ksort($this->_details);
				foreach ($this->_details as $timezone_id => $tz)
				{
					extract($tz);
					if ($value == $timezone_id) $selected = true;
					if ($continent !== $thiscontinent)
					{
						if ($thiscontinent !== '') $out[] = n.t.'</optgroup>';
						$out[] = n.t.'<optgroup label="'.gTxt($continent).'">';
						$thiscontinent = $continent;
					}

					$where = gTxt(str_replace('_', ' ', $city))
								.(!empty($subcity) ? '/'.gTxt(str_replace('_', ' ', $subcity)) : '').t
								/*."($abbr)"*/;
					$out[] = n.t.t.'<option value="'.htmlspecialchars($timezone_id).'"'.($value == $timezone_id ? ' selected="selected"' : '').'>'.$where.'</option>';
				}
				$out[] = n.t.'</optgroup>';
				return n.'<select'.( $select_id ? ' id="'.$select_id.'"' : '' ).' name="'.$name.'" class="list"'.
					($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
					'>'.
					($blank_first ? n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
					join('', $out).
					n.'</select>';
			}
			return '';
		}

		/**
		 * Build a matrix of timezone details
		 * @return	array	Array of timezone details indexed by timezone key
		 */
		function details()
		{
			return $this->_details;
		}

		/**
		 * Find a timezone key matching a given GMT offset.
		 * NB: More than one key might fit any given GMT offset,
		 * thus the returned value is ambiguous and merely useful for presentation purposes.
		 * @param	integer $gmtoffset
		 * @return	string	timezone key
		 */
		function key($gmtoffset)
		{
			return isset($this->_offsets[$gmtoffset]) ? $this->_offsets[$gmtoffset] : '';
		}

		 /**
		 * Is DST in effect?
		 * @param	integer $timestamp When?
		 * @param	string 	$timezone_key Where?
		 * @return	boolean	Yes, they are saving time, actually.
		 */
		function is_dst($timestamp, $timezone_key)
		{
			global $is_dst, $auto_dst;

			$out = $is_dst;
			if ($auto_dst && $timezone_key && timezone::is_supported())
			{
				$server_tz = date_default_timezone_get();
				if ($server_tz)
				{
					// switch to client time zone
					if (date_default_timezone_set($timezone_key))
					{
						$out = date('I', $timestamp);
						// restore server time zone
						date_default_timezone_set($server_tz);
					}
				}
			}
			return $out;
		}

		/**
		 * Check for run-time timezone support
		 * @return	boolean	All required timezone features are present in this PHP
		 */
		function is_supported()
		{
			return is_callable('date_default_timezone_set') && is_callable('timezone_abbreviations_list') && is_callable('date_create') &&
				is_callable('array_intersect_key') &&
				!defined('NO_TIMEZONE_SUPPORT');	// user-definable emergency brake
		}
	}
?>