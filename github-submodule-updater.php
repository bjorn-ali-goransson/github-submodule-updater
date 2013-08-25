<?php

require dirname(__FILE__) . '/lib/gitmodules-parser/gitmodules-parser.php';

function github_submodule_updater_get_branches($submodule){
  $result = json_decode(file_get_contents("https://api.github.com/repos/$submodule->author/$submodule->repo/branches"));

  if(!$result){
    throw new Exception('Couldn\'t get GitHub branches');
  }

  return $result;
}

function github_submodule_updater_update($submodule, $options = array()){
  $default_options = array(
    'temp_path' => rtrim(sys_get_temp_dir(), '/\\'),
    'branch' => 'master',
    'gitmodules_location' => getcwd(),
    'old_suffix' => '.old',
    'new_suffix' => '.new',
    'unzipped_suffix' => '.unzipped',
    'unzip' => function($zip_file, $unzip_to){
      if(!class_exists('PclZip')){
        require dirname(__FILE__) . '/class-pclzip.php';

        if(!class_exists('PclZip')){
          throw new Exception('PclZip could not be found or loaded');
        }
      }

      $archive = new PclZip($zip_file);

      if(!$archive->extract(PCLZIP_OPT_PATH, $unzip_to)){
        throw new Exception("Couldn't unzip ($zip_file to $unzip_to)");
      }
    },
    'enable_undo' => TRUE,
  );

  foreach($default_options as $key => $default_value){
    if(!isset($options[$key])){
      $options[$key] = $default_value;
    }
  }

  if(!isset($options['repo_dir'])){
    $options['repo_dir'] = $options['gitmodules_location'] . '/' . $submodule->path;
  }

  if(!isset($options['zip_url'])){
    $options['zip_url'] = "https://github.com/{$submodule->author}/{$submodule->repo}/archive/{$options['branch']}.zip";
  }

  if(!isset($options['github_subfolder_name'])){
    $options['github_subfolder_name'] = "{$submodule->repo}-{$options['branch']}";
  }



  /* DOWNLOAD ZIP TO TEMP */

  github_submodule_updater_create_directory($options['temp_path'], 'upload dir');
  
  $file_contents = file_get_contents($options['zip_url']);

  if(!$file_contents){
    throw new Exception("Couldn't download file ({$options['zip_url']})");
  }

  $temp_file = tempnam($options['temp_path'], 'git');

  if(!file_put_contents($temp_file, $file_contents)){
    throw new Exception("Couldn't write file ($temp_file)");
  }



  /* REMOVE/RECREATE 'UNZIPPED' DIRECTORY */

  $unzipped_dir = $options['repo_dir'] . $options['unzipped_suffix'];

  github_submodule_updater_remove_directory($unzipped_dir, 'unzipped dir');
  github_submodule_updater_create_directory($unzipped_dir, 'unzipped dir');



  /* UNZIP TO 'UNZIPPED' DIRECTORY */

  $options['unzip']($temp_file, $unzipped_dir);

  unlink($temp_file);



  /* MOVE FILES FROM 'UNZIPPED' SUB DIRECTORY TO NEW REPO DIRECTORY */
  
  $new_dir = $options['repo_dir'] . $options['new_suffix'];

  github_submodule_updater_remove_directory($new_dir, 'new dir');

  $unzipped_sub_dir = $unzipped_dir . '/' . $options['github_subfolder_name'];

  if(!file_exists($unzipped_sub_dir)){
    throw new Exception("Couldn't find unzipped dir ($unzipped_sub_dir)");
  }

  if(!rename($unzipped_sub_dir, $new_dir)){
    throw new Exception("Couldn't rename unzipped dir ($unzipped_sub_dir to $new_dir)");
  }



  /* REMOVE 'UNZIPPED' DIRECTORY */

  github_submodule_updater_remove_directory($unzipped_dir, 'unzipped dir');
  
  

  /* REMOVE OLD REPO DIRECTORY */

  $old_dir = $options['repo_dir'] . $options['old_suffix'];
  
  github_submodule_updater_remove_directory($old_dir, 'old dir');



  /* RENAME CURRENT REPO DIRECTORY */
  
  if($options['enable_undo']){
    if(file_exists($options['repo_dir']) && !rename($options['repo_dir'], $old_dir)){
      throw new Exception("Couldn't rename current repo dir ({$options['repo_dir']} to $old_dir)");
    }
  }
  
  

  /* RENAME NEW REPO DIRECTORY */
  
  if(!rename($new_dir, $options['repo_dir'])){
    throw new Exception("Couldn't rename new repo dir ($new_dir to {$options['repo_dir']})");
  }
}

function github_submodule_updater_undo_update($submodule, $options = array()){
  $default_options = array(
    'gitmodules_location' => getcwd(),
    'old_suffix' => '.old',
    'undone_suffix' => '.undone',
  );

  foreach($default_options as $key => $default_value){
    if(!isset($options[$key])){
      $options[$key] = $default_value;
    }
  }

  if(!isset($options['repo_dir'])){
    $options['repo_dir'] = $options['gitmodules_location'] . '/' . $submodule->path;
  }



  /* REMOVE UNDONE DIRECTORY */

  $undone_dir = $options['repo_dir'] . $options['undone_suffix'];

  github_submodule_updater_remove_directory($undone_dir, 'undone dir');



  /* RENAME CURRENT REPO DIRECTORY */
  
  if(!rename($options['repo_dir'], $undone_dir)){
    throw new Exception("Couldn't rename current repo dir ({$options['repo_dir']} to $undone_dir)");
  }
  
  

  /* RENAME OLD REPO DIRECTORY */

  $old_dir = $options['repo_dir'] . $options['old_suffix'];
  
  if(!rename($old_dir, $options['repo_dir'])){
    throw new Exception("Couldn't rename old repo dir ($old_dir to {$options['repo_dir']})");
  }
}

function github_submodule_updater_redo_update($submodule, $options = array()){
  $default_options = array(
    'gitmodules_location' => getcwd(),
    'old_suffix' => '.old',
    'undone_suffix' => '.undone',
  );

  foreach($default_options as $key => $default_value){
    if(!isset($options[$key])){
      $options[$key] = $default_value;
    }
  }

  if(!isset($options['repo_dir'])){
    $options['repo_dir'] = $options['gitmodules_location'] . '/' . $submodule->path;
  }



  /* REMOVE OLD DIRECTORY */

  $old_dir = $options['repo_dir'] . $options['old_suffix'];

  github_submodule_updater_remove_directory($old_dir, 'old dir');



  /* RENAME CURRENT REPO DIRECTORY */
  
  if(!rename($options['repo_dir'], $old_dir)){
    throw new Exception("Couldn't rename current repo dir ({$options['repo_dir']} to $old_dir)");
  }
  
  

  /* RENAME UNDONE REPO DIRECTORY */

  $undone_dir = $options['repo_dir'] . $options['undone_suffix'];
  
  if(!rename($undone_dir, $options['repo_dir'])){
    throw new Exception("Couldn't rename undone repo dir ($undone_dir to {$options['repo_dir']})");
  }
}

function github_submodule_updater_remove_directory($dir, $label){
  if(file_exists($dir)){
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $rmpath) {
      $rmpath->isFile() ? unlink($rmpath->getPathname()) : rmdir($rmpath->getPathname());
    }

    rmdir($dir);

    if(file_exists($dir)){
      throw new Exception("Couldn't remove $label ($dir)");
    }
  }
}

function github_submodule_updater_create_directory($dir, $label){
  if(!file_exists($dir)){
    if(!mkdir($dir, 0777, true)){
      throw new Exception("Couldn't create $label ($dir)");
    }
  }
}