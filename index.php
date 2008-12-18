<?php

session_start();

# Licence    : GPL 
# Created by : Matt Lankford
# Homepage   : Guiki.com

# START of configuration section
$_SESSION{'HOME'} = "Guiki";
$_SESSION{'USER'} = "admin";
$_SESSION{'PASS'} = "password";
# END of configuration section 

###### USE CAUTION BELOW HERE ######

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
	$_SESSION{'TEMPLATE'}	= 'templates/show.html';
	
	# Set the page to the default unless already set
	$_SESSION{'PAGE'} = ($_GET{'PAGE'}) ? clean($_GET{'PAGE'}) : $_SESSION{'HOME'};
}
function build()
{
	switch(clean($_GET{'MODE'}))
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
			break;
	}
}
function show()
{
	# Set the header to not cache files
	header ("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");

	set_content();
	
	set_template();
	
	write_wiki_links();
	 
	replace_variables();
	
	print $_SESSION{'OUTPUT'} ;
}
function edit()
{
	if(authenticate())
	{
		$_SESSION{'TEMPLATE'} = 'templates/edit.html';
	}
}
function save()
{
	if (authenticate())
	{
		if (file_put_contents('data/'.$_SESSION{'PAGE'},stripcslashes($_POST{'CONTENT'})))
		{
			$_SESSION{'FLASH'} = '<p>File '.$_SESSION{'PAGE'}.' was saved</p>';
		
		} else {
			
			$_SESSION{'ERROR'} .= "<p>There Was An Error Saving The File : ".$_SESSION{'PAGE'}."</p>";
		}
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
		
		} else {
			
			$_SESSION{'ERROR'} .= "<p>There was an error deleting file : ".$_SESSION{'PAGE'}."</p>";
		}
	}
}
function logout()
{
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
	} else {
	
		header('WWW-Authenticate: Basic realm="'.$_SESSION{'HOME'}.'"');
		header('HTTP/1.0 401 Unauthorized');

		$_SESSION{'ERROR'} .= "<p>You Must Login To Create, Edit or Delete files!</p>";
	
		return false;
	}
}
function clean($var)
{
	 return preg_replace("/[\`|\.|\\|\/|<\?]/","",$var);
}
function set_content()
{
	# Skip if the user is searching
	if ( ! clean($_POST{'SEARCH'}))
	{
		if (file_exists('data/'.$_SESSION{'PAGE'}))
		{
			$_SESSION{'CONTENT'} = implode( "", file('data/'.$_SESSION{'PAGE'}));
		
		} else {
		
			$_SESSION{'ERROR'} = "<p>This File Does not Exist!</p>";
			
			edit();
		}
	}
}
function set_template()
{
	if (file_exists($_SESSION{'TEMPLATE'}))
	{
		$_SESSION{'OUTPUT'} = implode( "", file($_SESSION{'TEMPLATE'}));
	
	} else {
	
		print "<p></p>Could not open template : ".$_SESSION{'TEMPLATE'}."</p>";
	}
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
	if ($_SESSION{'TEMPLATE'} != 'templates/edit.html')
	{
		$_SESSION{'CONTENT'} = preg_replace("/\[\[(.*?)\]\]/",writeLink("\\1"),$_SESSION{'CONTENT'});
	}
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
