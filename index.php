<?php

# Licence    : GPL 
# Created by : Matt Lankford
# Homepage   : Guiki.com

session_start();

# START of configuration section
$_SESSION{'HOME'}	= "Guiki";
$_SESSION{'USER'}	= "admin";
$_SESSION{'PASS'}	= "password";
# END of configuration section 

# !!!!USE CAUTION BELOW HERE!!!!

//TODO create a template system
//TODO make the menu an include via {MENU} tag
//TODO make the search for an include via {SEARCH} tag
//TODO make the footer an include via the {FOOTER} tag
//TODO make the header an include via the {HEADER} tag
//TODO make the editor an include via the {EDITOR} tag
//TODO make the sidebar an include via the {SIDEBAR} tag
//TODO maybe I make all things includeable via a system so you can create your own tags
//TODO make the login option on the menu a dropdown with logout, edit, delete and other admin functions
//TODO maybe use sqlite as the backend?
//TODO make a template dir and put templates in there and parse each one and parse them into session variables the same name as the file name
//TODO make a Sqlite version (or store files as XML)
//TODO add keywords area and description area
# change the urls to do this site.com/template/content
# so it could say site.com/show/Guiki
# or site.com/edit/Guiki
# or it could do this site.com/show/data/Guiki
# and site.com/show/Documentation/About
# or even do directorys like site.com/show/Documentation/About
# I can also create modules like site.com/install
# or site.com/backup
# problem is URL rewrites must be used or site.com?m=module&p=page
# or site.com?a=module/Documentation/About
# then I could include an .htaccess and code for either situation

setup();

build();

show();

function setup()
{
	# Clear old data
	$_SESSION{'ERROR'}		= '';
	$_SESSION{'FLASH'}		= '';
	$_SESSION{'OUTPUT'}		= '';
	$_SESSION{'CONTENT'}	= '';
	$_SESSION{'TEMPLATE'}	= 'show.html';
	
	# Set the mode to show if not already set
	$_SESSION{'MODE'} = ($_GET{'MODE'}) ? clean($_GET{'MODE'}) : 'SHOW';
	
	# Set the page to the default unless already set
	$_SESSION{'PAGE'} = ($_GET{'PAGE'}) ? clean($_GET{'PAGE'}) : $_SESSION{'HOME'};
}
function build()
{
	switch($_SESSION{'MODE'})
	{
		case 'LOGOUT':
			logout();
			break;
		case 'SEARCH':
			search();
			break;
		case 'DELETE':
			delete();
			break;
		case 'EDIT':
			edit();
			break;
		case 'SAVE':
			save();
			break;
		default:
			defaults();
			break;
	}
}
function show()
{
	header ("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");

	set_template();
	
	replace_variables();
	 
	print $_SESSION{'OUTPUT'} ;
}
function defaults()
{
	if (set_content())
	{
		write_wiki_links();

	} else {
		
		edit();
	}
}
function edit()
{
	if(authenticate())
	{
		$_SESSION{'TEMPLATE'} = 'edit.html';

		set_content();
	
	} else {
		
		$_SESSION{'ERROR'} .= "<p>You Must Be Logged In To Create Or Edit Files</p>";
	}
}
function save()
{
	if (authenticate())
	{
		if (file_put_contents('data/'.$_SESSION{'PAGE'},stripcslashes($_POST{'CONTENT'})))
		{
			$_SESSION{'FLASH'} = 'File '.$_SESSION{'PAGE'}.' was saved';
			
			set_content();
			
			write_wiki_links();
		}

	} else {
		
		$_SESSION{'ERROR'} .= "You Must Login To Save Files";

	}
}
function delete()
{
	if (authenticate())
	{
		if (unlink('data/'.$_SESSION{'PAGE'}))
		{
			$_SESSION{'FLASH'} = '<p>File '.$_SESSION{'PAGE'}.' was deleted</p>';

			$_SESSION{'PAGE'} = $_SESSION{'HOME'};
			
			set_content();
			
			write_wiki_links();
		}
	
	} else {

		$_SESSION{'ERROR'} .= "<p>You Must Login To Delete Files</p>";
	}
}
function logout()
{
	set_content();
	
	write_wiki_links();
	
	$_SERVER['PHP_AUTH_USER'] = '';
	
	authenticate();
}
function authenticate()
{
	if($_SESSION{'USER'} == clean($_SERVER['PHP_AUTH_USER']))
	{
		if ($_SESSION{'PASS'} == clean($_SERVER['PHP_AUTH_PW']))
		{
			return true;
		}
	}
	
	header('WWW-Authenticate: Basic realm="'.$_SESSION{'HOME'}.'"');
	header('HTTP/1.0 401 Unauthorized');

	$_SESSION{'ERROR'} .= "<p>You Are NOT Logged In!</p>";
	
	return false;
}
function clean($var)
{
	 return preg_replace("/[\`|\.|\\|\/|<\?]/","",$var);
}
function set_content()
{
	if (file_exists('data/'.$_SESSION{'PAGE'}))
	{
		$_SESSION{'CONTENT'} = implode( "", file('data/'.$_SESSION{'PAGE'}));
		
		return true;
	
	} else {
		
		return false;
	}
}
function set_template()
{
	$_SESSION{'OUTPUT'} = implode( "", file('templates/'.$_SESSION{'TEMPLATE'}));
}
function replace_variables()
{
	foreach($_SESSION as $key => $value)
	{
		if ( $key != 'OUTPUT')
		{
			$_SESSION{'OUTPUT'} = str_replace('{'.$key.'}',$value,$_SESSION{'OUTPUT'});
		}
	}
}
function write_wiki_links()
{
	$_SESSION{'CONTENT'} = preg_replace("/\[\[(.*?)\]\]/",writeLink("\\1"),$_SESSION{'CONTENT'});
}
function writeLink($PAGE)
{
	#TODO If logged in, write links to edit a page that does not exist
	#TODO If NOT logged in, just write as plain text
	#TODO May not be the greatest idea, how would someone login?
	
	return "<a href=\"index.php?MODE=SHOW&PAGE=$PAGE\">$PAGE</a>";
}
function search() 
{
	$search = clean($_POST{'SEARCH'});
	
	$_SESSION{'CONTENT'} = "<ul>\n";
	
	# create an unordered list of pages with text that matches $search
	foreach (findFiles() as $page)
	{
		$current = implode( "", file("data/$page") );
		
		if (preg_match("/$search/i",$current) )
		{
			$_SESSION{'CONTENT'} .= "<li>".writeLink($page)."</li>\n";
		}
	}
	
	$_SESSION{'CONTENT'} .= "</ul>\n";
}
function findFiles()
{
	# get a list of all files with letters in the name (this excludes . and ..)
	$handle = opendir('data');
	while( $file = readdir($handle) )
	{
		if(preg_match("/([A-Z]+)/i",$file))
		{
			$files[] = $file;
		}
	}
	return $files;
}
?>
