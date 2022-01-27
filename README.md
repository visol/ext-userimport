# ext-userimport

Make sure to upload your files containing user data to a secure folder!

### Setup a protected storage folder

1. Create a file protected "File Storage" record
2. Create a folder /protected/userimport in your webroot
3. Create a "Filemount" record for the folder
4. Add the Filemount to a "Backend Usergroup"

![File storage record](filestorages.png)

### Add your storage folder to the module configuration

Module configuration in you extension manager.

![Modul configuration](modulesettings.png)

Make shure to match the id to your previously created storage folder.

Example entry: `<uid-of-storage>:/<subfolder>/`

