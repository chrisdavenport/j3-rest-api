j3-rest-api
===========
This is an experimental REST API for Joomla 3.x and these are some rough notes to
get you started.  DO NOT PUT THIS ON A PRODUCTION SITE.  This is proof-of-concept
code that is far from being complete or stable.  There is no access level security
(yet) so any content in your website will be publicly exposed by this code.

## Introduction
This proof-of-concept demonstrates how a web services API might be created for
Joomla 3.x.  It runs as a standalone application which merges services that have
been added to otherwise completely unmodified components.  No core code is changed.

The web services code makes use of the RESTful router included in the
Joomla Platform code in Joomla 3.x.

Any and all feedback is welcome, particularly at this stage regarding the
architecture.  Please send your comments to chris.davenport@joomla.org.
 Pull requests also welcome of course. Thanks!

## Prerequisites
You must have an already installed and working installation of Joomla 3.x.

## Installation
Grab the code from GitHub: https://github.com/chrisdavenport/j3-rest-api and put
it in your Joomla web root.

Add the following lines to your .htaccess file:

```
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
```

Point a web browser at [path-to-Joomla]/api

You should get some JSON back.  This is the entry point service document described
below.

## Using the HAL Browser
You can browse the API interactively using Mike Kelly's HAL Browser.  As the /api/hal-browser
directory in this repository is corrupted in some weird way I haven't been able to fix, you
will need to download and install it yourself from https://github.com/mikekelly/hal-browser.

Once installed simply point a web browser at the following URL (adjusted for your environment):

```
http://www.example.com/path-to-Joomla/api/hal-browser/browser.html#http://www.example.com/path-to-Joomla/api
```
Important: You will need to add the following line to your /etc/config.json file
in order for the HAL Browser to work:

```
"absoluteHrefs": true
```

## Quick tour of the code
The “core” code lives in the new /api directory, which is the entry point for the
API application.

Basic configuration files go in /etc although the standard Joomla configuration.php
file is also loaded in order to get the database credentials.

A new /services directory will have been added to each component for which support
has been provided.  The currently supported components are: articles/content,
categories and weblinks.  They demonstrate how an extension can have web services
code added to it.  The current code does not make use of any component code,
and installing it does not overwrite any current code, but sharing model code at
least is something that should be seriously considered.

In the distributed code the Content-Type headers returned should be correct as per
the specification (eg. application/vnd.joomla.service.v1+hal+json”).  The HAL Browser
will work with these just fine, however if you use a web browser to access the
API directly they will not normally be recognised.  If you want to test the API
in a web browser without using the HAL Browser, then comment out the setMimeEncoding
line in the execute method in the /api/controller/base.php file.  The API will
then return the default Content-Type: application/json header.

## Entry point service document
Pointing a web browser at the api entry point: http://www.example.com/api will return the “service” document which lists the services
available via the API.  The format of this document is described in "application/vnd.joomla.service.v1 media type specification" referenced below.
The code responsible for handling this document can be found in 

```
/api/services/root/get.php
```

## Adding web services support to a component
Look in /components/com_content and /components/com_weblinks for examples of how this could be done.  The core web services code looks for a services.json file in each of the installed component directories.  If it finds one it automatically merges it into the services router map.

Here’s the router map for com_content:

```javascript
{
    "joomla:articles":"component/content/ArticlesList",
    "joomla:articles/:id":"component/content/Articles"
	"joomla:categories/:catid/articles":"component/content/ArticlesList"
}
```

Each line consists of a “public” route and the associated “internal” route.  So on the first line we have “joomla:articles” mapping to "component/content/ArticlesList", which means that a GET request to this URL:

```
http://www.example.com/api/joomla:articles
```

will cause the controller class ComponentContentArticlesListGet to be loaded from the file

```
/components/com_content/services/articles/list/get.php
```

The JLoader prefix “ComponentContentArticles” is automatically set up to point to the correct directory, so provided you have the correct class in the correct file the PHP loader will find it without further effort.

The document returned in this case will contain a (paginated) list of articles and is described in "application/vnd.joomla.list.v1 media type specification" referenced below.

The second line of the services.json file maps URLs such as

```
http://www.example.com/api/joomla:articles/1234
```

to the controller class ComponentContentArticlesGet in the file

```
/components/com_content/services/articles/get.php
```

The document returned in this case will contain a representation of the single article requested (by its id) and is described in "application/vnd.joomla.item.v1 media type specification" referenced below.

The fields returned for the articles resource are described in https://docs.google.com/a/joomla.org/document/d/1d5qQ16r1Bo1BlXXuyS_eFB4BQcfuSg05pn9hsMpAgqk/edit#heading=h.ygla5naoxuzt

The third line of the services.json file maps URLs such as

```
http://www.example.com/api/joomla:categories/383/articles
```

to the same controller as the first line.  In this instance it will return a list of all articles which are in category 383.

## Resource - database field mapping
In order to establish the mapping between the database fields and the fields exposed in the API, a JSON object describes the relationship so that in many (most?) cases, adding web services support to an existing component is mostly a matter of creating these map files.

The map is located in a resource.json file.  So for com_content, for example, you should look in the file

```
/components/com_content/services/articles/resource.json
```

The JSON object contained in this file is a simple list of key-value pairs.  All the fields represented by the key-value pairs will be included in a full representation of the resource.  Both keys and values are strings which have a specific format that describes the detailed mapping between the field in the API representation (the key) and the field in the model (database) representation (the value).

### Key format
The ABNF (RFC5234) syntax for the key is as follows:

```
[ objectName ] “/” fieldName [ “.” propertyName ]
```

where

<table>
	<tr>
		<td>objectName</td>
		<td>is the optional name of an object within the resource.  The default object name is “main” and refers to the resource itself.</td>
	</tr>
	<tr>
		<td>fieldName</td>
		<td>is the name of the field that will be assigned the value.</td>
	</tr>
	<tr>
		<td>propertyName</td>
		<td>is used where fieldName is an object and propertyName refers to a property of that object.</td>
	</tr>
</table>

### Value format
The ABNF (RFC5234) syntax for the value string is as follows:

```
transformName “:” definition
```

where

<table>
	<tr>
		<td>transformName</td>
		<td>is the name of a transform that will modify the value before passing it
		 to the API object.  This is often just a matter of type casting
		 (eg. “string” or “int” transforms) but more sophisticated transforms
		  are available (eg. “state”) and you can add your own or override the
		   standard ones.  See below for more information.</td>
	</tr>
	<tr>
		<td>definition</td>
		<td>is a string which is first parsed for model field names that will be substituted by their values before the resulting
		string is passed to the transform method.  The definition string may contain the names of model fields enclosed in curly brackets.
		These will be automatically replaced by the model values.  The field name may contain a “.” in which case the part before
		the dot refers to a JSON-encoded model field and the part after the dot refers to a field within that JSON-encoded data.
		The unpacking of JSON-encoded fields is handled automatically.</td>
	</tr>
</table>

Some examples:

<table>
	<tr>
		<td>string:{title}</td>
		<td>Retrieves the title field from the model and casts it to a string.</td>
	</tr>
	<tr>
		<td>string:post</td>
		<td>Returns the literal string “post” (without the quotes).</td>
	</tr>
	<tr>
		<td>string:/joomla:articles/{id}</td>
		<td>Retrieves the id field from the model and substitutes it into the definition string.  For example, if id has the value 987 then this will return the string “/joomla:articles/987”.</td>
	</tr>
</table>

The standard transforms are defined in the /api/transform directory and are
described here:

<table>
	<tr>
		<td>int</td>
		<td>Casts the definition string to an integer.</td>
	</tr>
	<tr>
		<td>string</td>
		<td>Casts the definition string to a string (duh!).</td>
	</tr>
	<tr>
		<td>boolean</td>
		<td>Casts the definition string to a boolean.</td>
	</tr>
	<tr>
		<td>datetime</td>
		<td>Returns an ISO 8601 date/time field [NOT IMPLEMENTED YET]</td>
	</tr>
	<tr>
		<td>float</td>
		<td>Returns “left”, “right”, “none” or “global”.</td>
	</tr>
	<tr>
		<td>state</td>
		<td>Returns “unpublished”, “published”, “trashed” or “archived”.</td>
	</tr>
	<tr>
		<td>target</td>
		<td>Returns “parent”, “new”, “popup”, “modal” or “global”.</td>
	</tr>
	<tr>
		<td>ynglobal</td>
		<td>Returns “yes”, “no” or “global”.</td>
	</tr>
</table>

## Adding a custom transform
Components may add custom transforms override the standard ones if required.
Create a /transform directory at the same level in the component directory structure
as the resource.json file and add the additional or override transform classes
in it.  There is an example of this in 

```
/components/com_content/services/transform/position.php
```

## Defining which fields to embed in a list representation
Because the list representation would not normally include a full representation of each of the embeddeded objects,
there is a simple JSON file that defines which fields are included.  The file for com_content is

```
/components/com_content/services/articles/list/embedded.json
```

and it contains something like this:

```javascript
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
```

The array is a simple list of field names to be included in the embedded representations.
Each entry must match a field name in the articles.json file.  The fields definitions from the resource.json file
used so that data in both single and list representations should match exactly.

## References and further reading

* Joomla Web Services Working Group http://docs.joomla.org/Web_Services_Working_Group
* Joomla CMS Web Services API Specification https://docs.google.com/document/d/1FVKGlV6BN6pu-YH2WR2pQHE3Ez7M6r7LD417GSw9ZSo/edit?usp=sharing
* application/vnd.joomla.base.v1 media type specification https://docs.google.com/document/d/11SqH-daKQV9SrFBMEpopjBk3vM1USIHnFWZB9rjJB94/edit?usp=sharing
* application/vnd.joomla.service.v1 media type specification https://docs.google.com/document/d/1wg3AcgStA26UwDcbHVV1bub4sa_BhsKfzAmX21eG-FM/edit?usp=sharing
* application/vnd.joomla.item.v1 media type specification https://docs.google.com/document/d/16xwxSDDPW0U1CG9l7JcwOyGvyjm7wv5zOSd9JwgF2iQ/edit?usp=sharing
* application/vnd.joomla.list.v1 media type specification https://docs.google.com/document/d/1PLym28MG5v1tWyvIyW-9483JNKh5AP21Fmsmg62plnA/edit?usp=sharing
* Joomla CMS Web Service API Implementation https://docs.google.com/document/d/1d5qQ16r1Bo1BlXXuyS_eFB4BQcfuSg05pn9hsMpAgqk/edit?usp=sharing
* Joomla CMS CLI Services API Specification https://docs.google.com/document/d/1wI3cSm3y4aa8n8rojJKpiF6RUpSl63WFuLgJj2WqW8o/edit?usp=sharing
