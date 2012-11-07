<?php
/*
Satellite Model
*/

// Load other required models
App::import('model','Group');

class Satellite extends AppModel {
    var $name = 'Satellite';
    
    // Define associations
    public $hasAndBelongsToMany = array(
        'Group' =>
            array(
                'className'              => 'Group',
                'joinTable'              => 'groups_satellites',
                'foreignKey'             => 'satellite_id',
                'associationForeignKey'  => 'group_id',
                'unique'                 => true
            )
    );
    
    public function default_element_json(){
        /*
        Loads all default groups and satellites into JSON for use on the homepage.
        
        Returns:
            JSON string representing default satellites and groups.
        */
        
        // Setup
        $default_array['groups'] = Array();
        $default_array['satellites'] = Array();
        
        // Load all default satellites and groups
        $default_groups = $this->Group->find('all', array(
            'conditions' => array(
                'Group.default_on_home' => 1
            )
        ));
        foreach($default_groups as $default_group){
            // Add the group ID to the default array
            array_push($default_array['groups'], $default_group['Group']['id']);
            
            // Add each of the group's satellites to the default array
            foreach($default_group['Satellite'] as $default_group_satellite){
                array_push($default_array['satellites'], array(
                    'name' => $default_group_satellite['name'],
                    'id' => $default_group_satellite['id']
                ));
            }
        }
        
        // Load all default satellites
        $default_satellites = $this->find('all', array(
            'conditions' => array(
                'Satellite.default_on_home' => 1
            )
        ));
        foreach($default_satellites as $default_satellite){
            // Add the satellite to the default array
            array_push($default_array['satellites'], array(
                'name' => $default_satellite['Satellite']['name'],
                'id' => $default_satellite['Satellite']['id']
            ));
        }
        
        return json_encode($default_array);
    }
    
    public function satellite_json($satellite_names = false, $group_names = false, $use_decode = true){
      /*
      Loads the specified satellite (or all of them if no argument passed) and formats it into JSON.
      
      @param $satellite_names: Names of the satellites to load.
      @param $group_names: Names of the groups to load.
      @param $use_decode: Whether or not URL decode is needed.
      
      Returns:
          JSON string representing the satellite(s).
          FALSE if no satellites can be loaded.
      */
      
      // Local variables
      $satellites = array();
      
      // Check if either some satellite or group names were set
      if ($satellite_names!=false || $group_names!=false){
        // Load the satellites from the groups
        if ($group_names!=false){
          $groups = $this->Group->find('all', array(
            'conditions' => array(
              'Group.name' => $group_names
            )
          ));
          
          // Loop through the satellites and add them to the array
          foreach ($groups as $group){
            foreach ($group['Satellite'] as $group_satellite){
              // Make sure the satellite hasn't all ready been added
              if (!array_key_exists($group_satellite['id'], $satellites)){
                $temp_satellite = array(
                  'Satellite' => $group_satellite,
                  'Group' => array($group['Group'])
                );
              
                // Add the satellite to the list
                $satellites[$group_satellite['id']] = $temp_satellite;
              } else {
                // Add the new group to the existing satellite entry
                array_push($satellites[$group_satellite['id']]['Group'], $group['Group']);
              }
            }
          }
        }
        
        // Load the specified satellites
        if ($satellite_names!=false){
          $specific_satellites = $this->find('all', array(
            'conditions' => array(
              'Satellite.name' => $satellite_names
            )
          ));
          
          // Loop through the satellites and add them to the array
          foreach ($specific_satellites as $specific_satellite){
            // Make sure the satellite hasn't been added all ready
            if (!array_key_exists($specific_satellite['Satellite']['id'], $satellites)){
              // Add the satellite to the list
              $satellites[$specific_satellite['Satellite']['id']] = $specific_satellite;
            }
          }
        }
      } else {
        // No satellites or groups specified, load the homepage satellites.
        $satellites = $this->find('all');
          
        // Loop through satellites and remove ones that aren't homepage visible
        foreach ($satellites as $satellite_key => $temp_satellite){
          $show_on_home = false;
          if ($temp_satellite['Satellite']['show_on_home']=='1'){
            $show_on_home = true;
          } else {
            // Check the groups
            foreach ($temp_satellite['Group'] as $temp_group){
              if ($temp_group['show_on_home']=='1'){
                $show_on_home = true;
              } else {
                $show_on_home = false;
              }
            }
            
            // Remove the satellite if needed
            if (!$show_on_home){
              unset($satellites[$satellite_key]);
            }
          }
        }
      }
      
      // Create a JSON object for the satellites
      $satellite_array = array();
      foreach ($satellites as $satellite){
        $temp_satellite = array(
          'id' => $satellite['Satellite']['id'],
          'name' => $satellite['Satellite']['name'],
          'description' => $satellite['Satellite']['description'],
          'groups' => array()
        );
          
        // Add all of the satellite's groups
        foreach($satellite['Group'] as $group){
          $temp_group = array(
            'id' => $group['id'],
            'name' => $group['name']
          );
              
          array_push($temp_satellite['groups'], $temp_group);
        }
          
        $satellite_array[$satellite['Satellite']['id']] = $temp_satellite;
      }
      
      var_dump(json_encode($satellite_array));
      
      // Return the JSON representation
      return json_encode($satellite_array);
    }
}
?>
