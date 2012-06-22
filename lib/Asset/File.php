<?php
namespace Asset;

class File
{
	private $filepath,
		$path,
		$directory,
		$name,
		$type,
		$filters,
		$vars = array();

	public function __construct($path, $vars = array())
	{
		if ($path == '\\/application.js')
			d_p_b();
		$pipeline = Pipeline::getCurrentInstance();
	
		$this->path = trim($path, '/\\');
		$this->directory = '.' === ($dirname = dirname($path)) ? '' : $dirname;
		
		$this->vars = $vars;
		
		$file = basename($path);
		$filename_parts = explode('.', $file);
		if (!isset($filename_parts[1]))
			vdump($path, 'no filename parts 1');
		$this->name = $filename_parts[0];
		$this->type = $filename_parts[1];

		$this->path_with_simple_filename = ('' === $this->directory ? '' : $this->directory . '/') . $this->name;
		$this->filepath = $pipeline->getFile($this->path_with_simple_filename, $this->type);
		
		$full_filename = explode('/', $this->filepath);
		$full_filename = end($full_filename);
		$full_filename_parts = explode('.', $full_filename);
		$this->filters = array_reverse(array_slice($full_filename_parts, 2)); //['less', 'php'] => ['php', 'less']

		if (in_array($this->type, array('html', 'css', 'js')))
			$pipeline->addDependency($this->filepath);
	}
	
	public function getFilepath()
	{
		return $this->filepath;
	}
	
	private function getProcessedContent()
	{
		$filters = $this->filters;
		if (class_exists('Filter\\' . ucfirst($this->type)))
			$filters[] = $this->type;
		
		$content = self::processFilters($this->filepath, $filters, $this->vars);


		if ($this->type != 'js' && $this->type != 'css')
			return $content; //no directives
		
		$new_content = '';

		foreach (explode("\n", $content) as $line)
		{
			if ((($this->type == 'js' || $this->type == 'css') && substr($line, 0, 3) == '//=') ||
			 ($this->type == 'js' && substr($line, 0, 2) == '#='))
			{
				$directive = explode(' ', trim(substr($line, 3)));
				
				$function = $directive[0];
				$arguments = array_slice($directive, 1);
				
				$new_content .= call_user_func_array(array($this, pascalize($function) . 'Directive'), $arguments) . "\n";
			}
			else
				$new_content .= $line . "\n";
		}
		
		return $new_content;
	}	

	public function process()
	{
		if (Pipeline::getCurrentInstance()->hasProcessedFile($this->filepath))
			return ' '; //hasProcessedFile will add it otherwise

		return $this->getProcessedContent();
	}
	
	public function __toString()
	{
		try {
			$e= $this->process();
			if (empty($e) || !is_string($e))
				vdump($e, $this->getFilepath(), file_get_contents($this->getFilepath()));
			return $e;
		} catch (Exception\Asset $e) {
			exit('Asset exception (' . $this->getFilepath() . ') : ' . $e->getMessage());
		} catch (\Exception $e) {
			exit('External exception (' . $this->getFilepath() . ') : ' . $e->getMessage());
		}
	}
	
	
	private function requireDirective($name)
	{
		$pipeline = Pipeline::getCurrentInstance();

		if ($pipeline->hasFile($file = $this->directory . $name, $this->type))
			return (string) new File($file . '.' . $this->type, $this->vars);
		else if ($pipeline->hasFile($file = $this->directory . $name . '/index', $this->type))
			return (string) new File($file . '.' . $this->type, $this->vars);
		else
			throw new Exception\FileNotFound($file, $type);
	}
	private function requireTreeDirective($name = '/')
	{
		return (string) new Tree($this->directory . $name, $this->type, $this->vars);
	}
	private function requireDirectoryDirective($name = '/')
	{
		return (string) new Directory($this->directory . $name, $this->type, $this->vars);
	}
	private function dependsOnDirective($name)
	{ //allows to depend on a file, even if this one isn't included
		$pipeline = Pipeline::getCurrentInstance();
		
		if (strpos($name, '.') === false)
			$type = $this->type;
		else
		{ //depending on another asset type. In example, //= depends_on image.png
			$name_parts = explode('.', $name);
			$type = $name_parts[1]; //"style" "css" *filters
		}

		$pipeline->addDependency($pipeline->getFile($name, $type));
	}
	
	
	static private function processFilters($path, $filters, $vars)
	{
		$pipeline = Pipeline::getCurrentInstance();
		$content = file_get_contents($path);
		
		foreach ($filters as $filter)
			$content = $pipeline->applyFilter($content, $filter, $path, $vars);
	
		return $content;
	}
}