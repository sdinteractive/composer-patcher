# Patcher
Applying generic patches using the `patch` tool using Composer's `script` feature.  
The patching is idempotent as much as the `patch` tool is, meaning patches will _not_ be re-applied if `patch` decides not to.

## Project setup

a) Create `composer-patches` directory in project root.

b) Place `.patch` patches in `composer-patches`

c) Additional scripts callbacks need to be added for automatic patching on `install` or `update` (root package only):
```json
  "scripts": {
    "post-install-cmd": "Inviqa\\Command::patch",
    "post-update-cmd": "Inviqa\\Command::patch"
  }
```
You can use whatever [Composer *Command* event](https://getcomposer.org/doc/articles/scripts.md#event-names) you want, 
or even [trigger the events manually](https://getcomposer.org/doc/articles/scripts.md#running-scripts-manually).  
Again, note that only *Command events* are supported. Please check the above link to see which ones are they.

d) the `patch` tool must be available
