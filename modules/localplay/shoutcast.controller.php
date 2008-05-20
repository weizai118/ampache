<?php
/*

 Copyright Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 

*/

/**
 * AmpacheShoutCast
 * This class handles the shoutcast extension this is kind of an ugly controller
 * ooh well you do what you can 
 */
class AmpacheShoutCast extends localplay_controller {

	/* Variables */
	private $version 	= '000001'; 
	private $description	= 'Outputs to a local shoutcast server'; 

	private $local_path; 
	private $pid; 
	private $playlist; 

	// Generated
	private $files = array(); 

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	public function __construct() { 

		// Nothing to do here really? 
	

	} // AmpacheShoutCast

	/**
	 * get_description
	 * Returns the description
	 */
	public function get_description() { 

		return $this->description; 
	
	} // get_description

	/**
	 * get_version
	 * This returns the version information
	 */
	public function get_version() { 

		return $this->version; 

	} // get_version

	/**
	 * is_installed
	 * This returns true or false if MPD controller is installed
	 */
	public function is_installed() { 

                $sql = "DESCRIBE `localplay_shoutcast`";
                $db_results = Dba::query($sql);

                return Dba::num_rows($db_results);

	} // is_installed

	/**
	 * install
	 * This function installs the MPD localplay controller
	 */
	public function install() { 

                /* We need to create the MPD table */
                $sql = "CREATE TABLE `localplay_shoutcast` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                        "`name` VARCHAR( 128 ) COLLATE utf8_unicode_ci NOT NULL , " .
                        "`owner` INT( 11 ) NOT NULL , " .
                        "`pid` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                        "`playlist` INT( 11 ) UNSIGNED NOT NULL DEFAULT '6600', " .
                        "`local_root` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                        "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" .
                        ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
                $db_results = Dba::query($sql);
		
		// Add an internal preference for the users current active instance
		Preference::insert('shoutcast_active','Shoutcast Active Instance','0','25','integer','internal'); 
		User::rebuild_all_preferences(); 

                return true;

	} // install

	/**
	 * uninstall
	 * This removes the localplay controller 
	 */
	public function uninstall() { 

                $sql = "DROP TABLE `localplay_shoutcast`";
                $db_results = Dba::query($sql);

		Preference::delete('shoutcast_active'); 

                return true;

	} // uninstall

	/**
	 * add_instance
	 * This takes key'd data and inserts a new MPD instance
	 */
	public function add_instance($data) { 

		foreach ($data as $key=>$value) { 
			switch ($key) { 
				case 'name': 
				case 'pid':
				case 'playlist': 
				case 'local_root': 
					${$key} = Dba::escape($value); 
				break;
				default: 

				break;
			} // end switch 
		} // end foreach

		$user_id = Dba::escape($GLOBALS['user']->id); 

		$sql = "INSERT INTO `localplay_shoutcast` (`name`,`pid`,`playlist`,`local_root`,`owner`) " . 
			"VALUES ('$name','$pid','$playlist','$local_root','$user_id')";
		$db_results = Dba::query($sql); 
		
		return $db_results; 

	} // add_instance

	/**
 	 * delete_instance
	 * This takes a UID and deletes the instance in question
	 */
	public function delete_instance($uid) { 
		
		$uid = Dba::escape($uid); 

		// Go ahead and delete this mofo!
		$sql = "DELETE FROM `localplay_shoutcast` WHERE `id`='$uid'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // delete_instance

	/**
 	 * get_instances
	 * This returns a key'd array of the instance information with 
	 * [UID]=>[NAME]
	 */
	public function get_instances() { 

		$sql = "SELECT * FROM `localplay_shoutcast` ORDER BY `name`"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[$row['id']] = $row['name']; 
		} 

		return $results; 

	} // get_instances

	/**
	 * get_instance
	 * This returns the specified instance and all it's pretty variables
	 * If no instance is passed current is used
	 */
	public function get_instance($instance='') { 

		$instance = $instance ? $instance : Config::get('shoutcast_active');
		$instance = Dba::escape($instance); 

		$sql = "SELECT * FROM `localplay_shoutcast` WHERE `id`='$instance'";  
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		return $row; 

	} // get_instance

	/**
	 * update_instance
	 * This takes an ID and an array of data and updates the instance specified
	 */
	public function update_instance($uid,$data) { 

		$uid 	= Dba::escape($uid); 
		$pid	= Dba::escape($data['pid']);
		$playlist	= Dba::escape($data['playlist']);
		$name	= Dba::escape($data['name']); 
		$local_root	= Dba::escape($data['local_root']); 

		$sql = "UPDATE `localplay_shoutcast` SET `pid`='$pid', `playlist`='$playlist', `name`='$name', `local_root`='$local_root' WHERE `id`='$uid'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // update_instance

	/**
	 * instance_fields
	 * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
	 * fields so that we can on-the-fly generate a form
	 */
	public function instance_fields() { 

		$fields['name'] 	= array('description'=>_('Instance Name'),'type'=>'textbox'); 
		$fields['pid'] 		= array('description'=>_('PID File'),'type'=>'textbox'); 
		$fields['playlist']	= array('description'=>_('Playlist File'),'type'=>'textbox'); 
		$fields['local_root']	= array('description'=>_('Local Path to Files'),'type'=>'textbox'); 

		return $fields; 

	} // instance_fields

	/**
	 * set_active_instance
	 * This sets the specified instance as the 'active' one
	 */
	public function set_active_instance($uid,$user_id='') { 

		// Not an admin? bubkiss!
		if (!$GLOBALS['user']->has_access('100')) { 
			$user_id = $GLOBALS['user']->id; 
		} 

		$user_id = $user_id ? $user_id : $GLOBALS['user']->id; 

		Preference::update('shoutcast_active',$user_id,intval($uid)); 
		Config::set('shoutcast_active',intval($uid),'1'); 

		return true; 

	} // set_active_instance	

	/**
	 * get_active_instance
	 * This returns the UID of the current active instance
	 * false if none are active
	 */
	public function get_active_instance() { 


	} // get_active_instance

	/**
	 * add
	 * This takes a single object and adds it in, it uses the built in 
	 * functions to generate the URL it needs
	 */
	public function add($object) { 

		// Take the filename and strip off the catalog's root_path and put our
		// prefix onto it
		$filename = $object->file; 
		$catalog = new Catalog($object->catalog); 

		$filename = str_replace($catalog->path,$this->local_path,$filename); 

		$this->files[] = $filename; 

		return true; 

	} // add

	/**
	 * delete_track
	 * This must take a single ID (as passed by get function) from Ampache
	 * and delete it from the current playlist
	 */
	public function delete_track($object_id) { 

		return true;

	} // delete_track
	
	/**
	 * clear_playlist
	 * This deletes the entire MPD playlist... nuff said
	 */
	function clear_playlist() { 

		return true;

	} // clear_playlist

	/**
	 * play
	 * This just tells MPD to start playing, it does not
	 * take any arguments
	 */
	function play() { 

		// If we have no files[] then just HUP the server nothing else
		if (!count($this->files)) { 
			$this->send_command('hup'); 
		} 
		else { 
			$this->write_playlist(); 
			$this->send_command('hup'); 
		} 

		return true;

	} // play

	/**
	 * stop
	 * This just tells MPD to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 

		return true;

	} // stop

	/**
	 * skip
	 * This tells MPD to skip to the specified song
	 */
	function skip($song) { 

		return true; 

	} // skip

	/**
	 * This tells MPD to increase the volume by 5
	 */
	public function volume_up() { 

		return true;

	} // volume_up

	/**
	 * This tells MPD to decrese the volume by 5
	 */
	public function volume_down() { 

		return true;
		
	} // volume_down

	/**
	 * next
	 * This just tells MPD to skip to the next song 
	 */
	public function next() { 

		return true;

	} // next

	/**
	 * prev
	 * This just tells MPD to skip to the prev song
	 */
	public function prev() { 

		return true;
	
	} // prev

	/**
	 * pause
	 */
	public function pause() { 
		
		return true;

	} // pause 

        /**
        * volume
        * This tells MPD to set the volume to the parameter
        */
       public function volume($volume) {

               return true;

       } // volume

       /**
        * repeat
        * This tells MPD to set the repeating the playlist (i.e. loop) to either on or off
        */
       public function repeat($state) {
	
       		return true;

       } // repeat


       /**
        * random
        * This tells MPD to turn on or off the playing of songs from the playlist in random order
        */
       public function random($onoff) {

               return true;

       } // random

       /**
        * move
        * This tells MPD to move song from SrcPos to DestPos
        */
       public function move($SrcPos, $DestPos) {

        	return true;
	} // move

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that MPD currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	public function get() { 

		return array(); 

	} // get

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method support
	 */
	public function status() { 

		return array();

	} // get_status

	/**
	 * connect
	 * This functions creates the connection to MPD and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	public function connect() { 
	
		// We could do some kind of check to see that the shoutcast server is up
		return true; 

	} // connect

	/**
	 * get_pid
	 * This returns the pid for the current instance
	 */
	public function get_pid() { 

		// Read and clean!
		$pid = intval(trim(file_get_contents($this->pid))); 

		if (!$pid) { 
			debug_event('Shoutcast','Unable to read PID from ' . $this->pid,'1'); 
		}

		return $pid; 

	} // get_pid

	/**	
	 * write_playlist	
 	 * This takes the files that we've got in our array and writes them out
	 */ 
	public function write_playlist() { 

		$string = implode("\n",$this->files); 
		
		$handle = fopen($this->playlist,"w"); 

		if (!is_resource($handle)) { 
			debug_event('Shoutcast','Unable to open ' . $this->playlist . ' for writing playlist file','1'); 
		} 

		fwrite($handle,$string); 
		fclose($handle); 
		
		return true; 

	} // write_playlist

	/**
	 * send_command
	 * This is the single funciton that's used to send commands to the 
	 * shoutcast server, one function makes it easier to ensure we've escaped our input
	 */
	public function send_command($command,$options=array()) { 

		// Just incase someone uses some crazy syntax
		$command = strtolower($command); 

		switch ($command) { 
			case 'hup': 
				$pid = $this->get_pid(); 
				if (!$pid) { return false; } 
				$command = 'kill -l HUP ' . escapeshellarg($pid); 
				system($command,$return); 
				debug_event('Shoutcast','Issued ' . $command . ' and received ' . $return,'3'); 
				return true; 
			break; 
			default: 
				return false; 
			break;  
		} // end switch on the commands we allow

		return false; 

	} // send_command

} //end of AmpacheShoutcast

?>
