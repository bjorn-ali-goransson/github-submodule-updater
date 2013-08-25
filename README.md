PHP GitHub submodules updater
=============================

Uses [PHP .gitmodules parser](https://github.com/bornemix/gitmodules-parser). This small API can download latest version from submodule branches of submodules defined in .gitmodules. It will not currently download nested submodules. It will also save the old folder, if any, to a foobar.old folder, so the update can be undone and, if wanted, redone.

All functions require a $submodule as a parameter, so read the [PHP .gitmodules parser](https://github.com/bornemix/gitmodules-parser) documentation for this.

Functions
---------

### github_submodule_updater_get_branches($submodule)

Returns an array of all branches for the specified submodule. Uses [GitHub Repos API v3](http://developer.github.com/v3/repos/#list-branches), so the returned branch objects with have a ```$branch->name``` property along with a ```$branch->commit``` property containing info about the latest commit.

### github_submodule_updater_update_submodule_with_branch($submodule, $options = array())

Updates the specified submodule to the latest version of branch 'master', and saves the existing version in a folder haveing the same name but ending with .old (for undoing updates). If that folder already exists, it will be deleted if downloading/unzipping of the package is successful. Uses PclZip for unzipping (but keep reading).

```$options``` is an array containing any (or none) of the following key/value pairs:

* ```temp_path``` is the path to which the zip file is saved. Defaults to the temp directory of the OS.
* ```branch``` is the branch from which to get the latest version. Defaults to ```master```.
* ```gitmodules_location``` is the location from which the .gitmodules paths will be relative. Defaults to the current working directory.
* ```old_suffix``` is the suffix that is appended to the submodule directory where version that was current before this update will be stored. Defaults to ```.old```.
* ```new_suffix``` is the suffix that is appended to the submodule directory where the latest version of the submodule will initially be stored. Defaults to ```.new```.
* ```unzipped_suffix``` is the suffix that is appended to the submodule directory where the contents of the package will be stored. Defaults to  ```.unzipped```.
* ```unzip``` is the *function* (closure) that does the unzipping of the package. Should have the signature ```function($zip_file, $unzip_to)```, the parameters of which are both paths. Defaults to using PclZip (bundled with this library).
* ```enable_undo``` specifies wether to save the current submodule to another directory for potential later undoing. Defaults to ```TRUE```.

### github_submodule_updater_undo_update($submodule, $options = array())

Undo's the latest update by renaming the current submodule directory to an 'undone' directory (for later potential redoing) and renaming the old, previously saved directory to the current directory name.

```$options``` is an array containing any (or none) of the following key/value pairs:

* ```gitmodules_location``` is the location from which the .gitmodules paths will be relative. Defaults to the current working directory.
* ```old_suffix``` is the suffix that is appended to the submodule directory where version that was current before this update is stored. Defaults to ```.old```.
* ```undone_suffix``` is the suffix that is appended to the submodule directory where the updated version of the submodule will be stored. Defaults to ```.undone```.

### github_submodule_updater_redo_update($submodule, $options = array())

Redo's the latest undo by renaming the current submodule directory to an 'old' directory (for yet another later potential undoing) and renaming the submodule residing in the 'undone' directory to the current directory name.

```$options``` is an array containing any (or none) of the following key/value pairs:

* ```gitmodules_location``` is the location from which the .gitmodules paths will be relative. Defaults to the current working directory.
* ```old_suffix``` is the suffix that is appended to the submodule directory where version that was current before this redo will be stored. Defaults to ```.old```.
* ```undone_suffix``` is the suffix that is appended to the submodule directory where the updated version of the submodule is stored. Defaults to ```.undone```.