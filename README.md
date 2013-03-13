j3-rest-api
===========
These are some rough notes on the experimental REST API for Joomla 3.x.  DO NOT PUT THIS ON A PRODUCTION SITE.  This is proof-of-concept code that is far from being complete or stable.  There is no access level security (yet) so any content in your website will be publicly exposed by this code.

## Introduction
This proof-of-concept demonstrates how a web services API might be created for Joomla 3.x as a standalone application which merges services that have been added to otherwise completely unmodified components.  No core code is changed.

The web services code makes use of the RESTful router included in the Joomla Platform code in Joomla 3.x.

Any and all feedback is welcome, particularly at this stage regarding the architecture.  Please send your comments to chris.davenport@joomla.org.  Pull requests also welcome of course. Thanks!

## Prerequisites
You must have an already installed and working installation of Joomla 3.x.

## Installation
Grab the code from GitHub: https://github.com/chrisdavenport/j3-rest-api and put it in your Joomla web root.

Add the following lines to your .htaccess file:

	# If the requested path and file is not /index.php and the request
	# has not already been internally rewritten to the index.php script
	RewriteCond %{REQUEST_URI} !^/index\.php
	# and the request is for something within the api folder
	RewriteCond %{REQUEST_URI} /api/ [NC]
	# and the requested path and file doesn't directly match a physical file
	RewriteCond %{REQUEST_FILENAME} !-f
	# and the requested path and file doesn't directly match a physical folder
	RewriteCond %{REQUEST_FILENAME} !-d
	# internally rewrite the request to the API index.php script
	RewriteRule .* api/index.php [L]

Point a web browser at [path-to-Joomla]/api

You should get some JSON back.

## Quick tour of the code
The “core” code lives in the new /api directory, which is the entry point for the API application.

Basic configuration files go in /etc although the current version doesn’t really use them because it loads the standard Joomla configuration.php file to get the database credentials.

A new /services directory will have been added to /components/com_content.  This demonstrates how an extension can have web services code added to it.

In the distributed code the Content-Type headers returned have been set to application/json so as to make it easy to test in a web browser.  However, the corrent Content-Types (eg. application/vnd.joomla.service.v1+hal+json” may be returned by uncommenting a line in the get.php files.

## Entry point service document
Pointing a web browser at the api entry point: http://www.example.com/api will return the “service” document which lists the services available via the API.  The format of this document is described in https://docs.google.com/a/joomla.org/document/d/1wg3AcgStA26UwDcbHVV1bub4sa_BhsKfzAmX21eG-FM/edit

The code responsible for handling this document can be found in 

/api/services/root/get.php

## Extending support for a component
Look at /components/com_content for an example of how this could be done.  The core web services code looks for a services.json file in each of the installed component directories.  If it finds one it automatically merges it into the services router map.

Here’s the one for com_content:

	{
	    "joomla:articles":"component/content/ArticlesList",
	    "joomla:articles/:article_id":"component/content/Articles"
	}

Each line consists of a “public” route and the associated “internal” route.  So on the first line we have “joomla:articles” mapping to "component/content/ArticlesList", which means that a GET request to this URL:

http://www.example.com/api/joomla:articles

will cause the controller class ComponentContentArticlesListGet to be loaded from the file

/components/com_content/services/articles/list/get.php

The JLoader prefix “ComponentContentArticles” is automatically set up to point to the correct directory, so provided you have the correct class in the correct file the PHP loader will find it without further effort.

The document returned in this case will contain a (paginated) list of articles and is described in https://docs.google.com/a/joomla.org/document/d/1PLym28MG5v1tWyvIyW-9483JNKh5AP21Fmsmg62plnA/edit

The second line of the JSON file maps URLs such as

http://www.example.com/api/joomla:articles/1234

to the controller class ComponentContentArticlesGet in the file

/components/com_content/services/articles/get.php

The document returned in this case will contain a representation of the single article requested (by its id) and is described in https://docs.google.com/a/joomla.org/document/d/16xwxSDDPW0U1CG9l7JcwOyGvyjm7wv5zOSd9JwgF2iQ/edit

The fields returned for the articles resource are described in https://docs.google.com/a/joomla.org/document/d/1d5qQ16r1Bo1BlXXuyS_eFB4BQcfuSg05pn9hsMpAgqk/edit#heading=h.ygla5naoxuzt

In order to establish the mapping between the database fields and the fields exposed in the API, a JSON object describes the relationship so that in many (most?) cases, adding web services support to an existing component is mostly a matter of creating these map files.

The map is located in a resource.json file.  So for com_content, for example, you should look in the file

/components/com_content/services/articles/articles.json

The JSON object contained in this file is a simple list of key-value pairs.  All the fields represented by the key-value pairs will be included in a full representation of the resource.  Both keys and values are strings which have a specific format that describes the detailed mapping between the field in the API representation (the key) and the field in the model (database) representation (the value).

### Key format
The syntax for the key is as follows:
[ objectName ] “/” fieldName [ “.” propertyName ]

where

objectName
	is the optional name of an object within the resource.  The default object name is “main” and refers to the resource itself.
	fieldName
	is the name of the field that will be assigned the value.
	propertyName
	is used where fieldName is an object and propertyName refers to a property of that object.
	
### Value format
The syntax for the value string is as follows:

transformName “:” definition

where

transformName
	is the name of a transform that will modify the value before passing it to the API object.  This is often just a matter of type casting (eg. “string” or “int” transforms) but more sophisticated transforms are available (eg. “state”) and you can add your own or override the standard ones.  Look for methods with the “transform” prefix (eg. “string” calls the “transformString” method in the controller).
	definition
	is a string which is first parsed for model field names that will be substituted by their values before the resulting string is passed to the transform method.
	
The definition string may contain the names of model fields enclosed in curly brackets.  These will be automatically replaced by the model values.  The field name may contain a “.” in which case the part before the dot refers to a JSON-encoded model field and the part after the dot refers to a field within that JSON-encoded data.  The unpacking of JSON-encoded fields is handled automatically.

Some examples:

string:{title}
	Retrieves the title field from the model and casts it to a string.
	string:post
	Returns the literal string “post” (without the quotes).
	string:/joomla:articles/{id}
	Retrieves the id field from the model and substitutes it into the definition string.  For example, if id has the value 987 then this will return the string “/joomla:articles/987”.
	
The following transforms are available by default:

int
	Casts the definition string to an integer.
	string
	Casts the definition string to a string (duh!).
	boolean
	Casts the definition string to a boolean.
	datetime
	Returns an ISO 8601 date/time field [NOT IMPLEMENTED YET]
	state
	Returns “unpublished”, “published”, “trashed” or “archived”.
	ynglobal
	Returns “yes”, “no” or “global”.
	
The following transforms are added for the com_content services:-

float
	Returns “left”, “right”, “none” or “global”.
	position
	Returns “above”, “below”, “split” or “global”.
	target
	Returns “parent”, “new”, “popup”, “modal” or “global”.


Because the list representation would not normally include a full representations of each of the embedded objects, there is a simple JSON file that defines which fields are included.  The file for com_content is

/components/com_content/services/articles/list/embedded.json

and it contains something like this:

	{
	    "embedded":
	    [
	            "/access",
	            "/featured",
	            "/introText",
	            "/language",
	            "_links/self.href",
	            "_links/joomla:assets.href",
	            "_links/joomla:categories.href",
	            "_links/joomla:checkout.href",
	            "_links/joomla:checkout.method",
	            "_links/joomla:introImage.href",
	            "_links/joomla:introImage.float",
	            "_links/joomla:introImage.title",
	            "_links/joomla:introImage.caption",
	            "metadata/authorName",
	            "/ordering",
	            "publish/alias",
	            "publish/created",
	            "publish/publishDown",
	            "publish/publishUp",
	            "/state",
	            "/title"
	    ]
	}

The array is a simple list of field names to be included in the embedded representations.  Each entry must match a field name in the articles.json file.  The fields definitions from the articles.json file used so that data in both single and list representations should match exactly.
