ZipArchive64
============

Not all files can be packed by ZipArchive. The problem is always the name for file or path when it has special characters (from UTF-8). Unfortunately you fully lose your character encoding and you cannot convert them back.

The solution or workaround for this behaviour is the file name and path name encoding to base64.