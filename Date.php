<?php
/**
 * SimplePie
 *
 * A PHP-Based RSS and Atom Feed Framework.
 * Takes the hard work out of managing a complete RSS/Atom solution.
 *
 * Copyright (c) 2004-2012, Ryan Parman, Geoffrey Sneddon, Ryan McCue, and contributors
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * 	* Redistributions of source code must retain the above copyright notice, this list of
 * 	  conditions and the following disclaimer.
 *
 * 	* Redistributions in binary form must reproduce the above copyright notice, this list
 * 	  of conditions and the following disclaimer in the documentation and/or other materials
 * 	  provided with the distribution.
 *
 * 	* Neither the name of the SimplePie Team nor the names of its contributors may be used
 * 	  to endorse or promote products derived from this software without specific prior
 * 	  written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
 * AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package SimplePie
 * @version 1.3.1
 * @copyright 2004-2012 Ryan Parman, Geoffrey Sneddon, Ryan McCue
 * @author Ryan Parman
 * @author Geoffrey Sneddon
 * @author Ryan McCue
 * @link http://simplepie.org/ SimplePie
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

if ( ! class_exists( 'SimplePie', false ) ) {
    require_once( ABSPATH . WPINC . '/class-simplepie.php' );
}

/**
 * Date Parser
 *
 * @package SimplePie
 * @subpackage Parsing
 */
class UpdateStar_SimplePie_Parse_Date extends SimplePie_Parse_Date
{
	/**
	 * Get the object
	 *
	 * @access public
	 */
	public static function get()
	{
		static $object;
		if (!$object)
		{
			$object = new UpdateStar_SimplePie_Parse_Date;
		}
		return $object;
	}

	/**
	 * Parse a superset of W3C-DTF (allows hyphens and colons to be omitted, as
	 * well as allowing any of upper or lower case "T", horizontal tabs, or
	 * spaces to be used as the time seperator (including more than one))
	 *
	 * @access protected
	 * @return int Timestamp
	 */
	public function date_w3cdtf($date)
	{
		static $pcre;
		if (!$pcre)
		{
			$year = '([0-9]{4})';
			$month = $day = $hour = $minute = $second = '([0-9]{2})';
			$decimal = '([0-9]*)';
			$zone = '(?:(Z)|([+\-])([0-9]{1,2}):?([0-9]{1,2}))';
			$pcre = '/^' . $year . '(?:-?' . $month . '(?:-?' . $day . '(?:[Tt\x09\x20]+' . $hour . '(?::?' . $minute . '(?::?' . $second . '(?:.' . $decimal . ')?)?)?' . $zone . ')?)?)?$/';
		}
		if (preg_match($pcre, $date, $match))
		{
			/*
			Capturing subpatterns:
			1: Year
			2: Month
			3: Day
			4: Hour
			5: Minute
			6: Second
			7: Decimal fraction of a second
			8: Zulu
			9: Timezone ±
			10: Timezone hours
			11: Timezone minutes
			*/

			// Fill in empty matches
			for ($i = count($match); $i <= 3; $i++)
			{
				$match[$i] = '1';
			}

			for ($i = count($match); $i <= 7; $i++)
			{
				$match[$i] = '0';
			}

			// Numeric timezone
			if (isset($match[9]) && $match[9] !== '')
			{
				$timezone = $match[10] * 3600;
				$timezone += $match[11] * 60;
				if ($match[9] === '-')
				{
					$timezone = 0 - $timezone;
				}
			}
			else
			{
				$timezone = 0;
			}

			// Convert the number of seconds to an integer, taking decimals into account
			$second = round($match[6] + (int)$match[7] / pow(10, strlen($match[7])));

			return gmmktime($match[4], $match[5], $second, $match[2], $match[3], $match[1]) - $timezone;
		}
		else
		{
			return false;
		}
	}
}

