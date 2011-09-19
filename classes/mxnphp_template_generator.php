<?php
class mxnphp_template_generator extends mxnphp_code_generator{
	public function create(){
		$this->template_dir = $this->config->document_root."templates/".$this->config->theme."/".$this->table->table_name.'/'; 
		if(!file_exists($this->template_dir)){
			mkdir($this->template_dir);
		}
		$this->create_index_template();
		$this->create_edit_index_template();
		$this->create_tabs_template();
		$this->create_listing_template();
		$this->create_new_template();	
		$this->create_edit_template();
	}
	private function create_index_template(){
		$template = <<<EOD
<?php 
\$this->tab = "{$this->table->table_name}";
\$this->include_template("tabs",\$this->tab);
\$this->include_template("{$this->class_name}_listing",\$this->tab);
\$this->include_template("new_{$this->class_name}",\$this->tab);
?>
EOD;
		$template_file = fopen($this->template_dir."index.php",'w+');
		fwrite($template_file,$template);
	}
	private function create_edit_index_template(){
		$template = <<<EOD
<?php 
\$this->tab = "edit";
\$this->include_template("tabs","{$this->table->table_name}");
\$this->include_template("{$this->class_name}_listing","{$this->table->table_name}");
\$this->include_template("new_{$this->class_name}","{$this->table->table_name}");
\$this->include_template("edit_{$this->class_name}","{$this->table->table_name}");
?>	
EOD;
		$template_file = fopen($this->template_dir."edit.php",'w+');
		fwrite($template_file,$template);
	}
	private function create_tabs_template(){
		$class = ucwords($this->class_name);
		$name = ucwords($this->table->table_name);
		$tabs = <<<EOD
<h1 class='title'>{$name}</h1>
<div class='tabs'>
	<a href='#' <?php echo \$this->tab == '{$this->table->table_name}' ? "class='on'" : '' ?>>{$name}</a>
	<a href='#' <?php echo \$this->tab == 'new' ? "'class='on'" : '' ?>>{$this->texts->create_new} {$class}</a>
	<?php echo (\$this->tab == 'edit') ? "<a href='#' class='on'>{$this->texts->edit} {$class}</a>" : '';?>
	<div class='clear'></div>
</div>
EOD;
		$template_file = fopen($this->template_dir."tabs.php",'w+');
		fwrite($template_file,$tabs);
	}
	private function create_listing_template(){
		$listing_header = "<tr class='header'>";
		if(isset($this->table->list_cells)){
			foreach($this->table->list_cells as $title => $contents){
				$listing_header .= "
			<th>$title</th>";
				$cells .= $this->create_cell(explode(",",$contents));
			}
		}else{
			foreach($this->table->inputs as $cell => $array){
				$array = explode(",",$array);
				$listing_header .= "
			<th>{$array[0]}</th>";
			}
		}
		$listing_header .= "
			<th class='icon' >Edit</th>
			<th class='icon' >Delete</th>
		</tr>";
		$listings = <<<EOD
<div class="center <?php echo \$this->tab == '{$this->table->table_name}' ? "" : "hidden"?>">
	<form method='post' action='/{$this->table->table_name}/' accept-charset='utf-8' class='navigation' >
		<p>
			<?php	
			\$baselink = isset(\$_REQUEST['q']) ? "/{$this->table->table_name}/q=".urlencode(\$_REQUEST['q'])."/" : "/{$this->table->table_name}/";
			\$this->user_pagination->echo_paginate(\$baselink,'p',8,"pagination"); 
			?>
			<input type='text' name='q' value='Search' class='search_input' title='Search' />
		</p>
	</form>
	<table>
	$listing_header
		<?php
			\$gray = ''; 
			foreach(\$this->{$this->table->table_name} as \${$this->class_name}){
				echo "<tr class='\$gray'>";
				{$cells}echo '<td class="icon" ><a href="/{$this->table->table_name}/edit/'.\${$this->class_name}->{$this->table->key}.'" >';
				\$this->print_img_tag("edit.png","edit");
				echo '</a></td>';
				echo '<td class="icon" ><a class="delete" href="/{$this->table->table_name}/destroy/'.\${$this->class_name}->{$this->table->key}.'" >';
				\$this->print_img_tag("delete.png","delete");
				echo '</a></td></tr>';
				\$gray = (\$gray=="")?'gray':'';
			}
		?>
	</table>
</div>
EOD;
		$template_file = fopen($this->template_dir.$this->class_name."_listing.php",'w+');
		fwrite($template_file,$listings);
	}
	private function create_new_template(){
		$this->current_template = 'new';
		$template_file = fopen($this->template_dir.'new_'.get_class($this->table).".php",'w+');
		$content = $this->create_sections();
		$sections = implode("
		</div><div class='clear'></div></div>
		<div class='container'><div class='internal'>",$content->sections);
		$sections = "
	<form action='/{$this->table->table_name}/create/' method='post' accept-charset='utf-8' id='create_form' />
		<div class='container on'><div class='internal'>".$sections."
		</div><div class='clear'></div></div>
	</form>
";
		$template_content = "<div class='center hidden'>".$content->menu.$sections."</div>";
		fwrite($template_file,$template_content);
	}
	private function create_edit_template(){
		$this->current_template = 'edit';
		$template_file = fopen($this->template_dir.'edit_'.get_class($this->table).".php",'w+');
		$content = $this->create_sections();
		$sections = $content->sections[0];
		for($i=1;$i<count($content->sections);$i++){
			$sections .= "
			</form>
		</div><div class='clear'></div></div>
		<div class='container'><div class='internal'>
			<form action='/{$this->table->table_name}/update/{$i}' method='post' accept-charset='utf-8' id='create_form' />".$content->sections[$i];
		}
		$sections = "
	<div id='edit_sections'>
		<div class='container on'><div class='internal'>
			<form action='/{$this->table->table_name}/update/0' method='post' accept-charset='utf-8' id='create_form' />".$sections."
			</form>
		</div><div class='clear'></div></div>
	</div>
";
		$template_content = "<div class='center'>".$content->menu.$sections."</div>";
		fwrite($template_file,$template_content);
	}
	private function create_sections(){
		$menu = "	
	<ul class='menu'>";
		$on = "class='on'";
		$i = 0;
		foreach($this->table->sections as $title => $inputs){
			$menu .= "
		<li><a href='#' $on>$title</a></li>";
			$sections[$i++] = $this->create_section($title,$inputs);			
			$on = "";
		}
		$menu .= "
	</ul>";
		$content->menu = $menu;
		$content->sections = $sections;
		return $content;
	}
	private function create_section($title,$inputs){
		$inputs = explode(',',$inputs);
		foreach($inputs as $input){
			$section .= $this->create_input($input,$this->table->inputs[$input]);
		}
		$hidden_id = $this->current_template == 'edit' ? "<input type='hidden' name='{$this->class_name}_{$this->table->key}' id='{$this->class_name}_{$this->table->key}' value='<?php echo \$this->edit_{$this->class_name}->{$this->table->key}; ?>'/>" : "";
		return $section."
			<p>$hidden_id<input type='submit' class='save' value='' /></p>";		
	}
	private function create_input($field,$attrs){
		$attrs = explode(',',$attrs);
		$name = $field."_input";
		$label = $attrs[0];
		$class = "";
		if(isset($attrs[2])){
			if(strstr($attrs[2],'required')){
				$label .= "*";
			}
			$class = $attrs[2];
		}
		$type = $attrs[1];
		$value = $this->current_template == 'edit' ? "value='<?php echo \$this->edit_{$this->class_name}->$field; ?>'" : "";
		if($type == 'text'){
			$input = "
			<p><label for='$name'>$label</label></p>
			<p><input type='text' name='$name' class='$class' $value /></p>
			";
		}else if($type == 'password'){
			$input = "
			<p><label for='$name'>$label</label></p>
			<p><input type='password' name='$name' class='$class password' /></p>
			
			<p><label for='{$name}_confirm'>{$this->texts->confirm} $label</label></p>
			<p><input type='password' name='{$name}_confirm' class='$class confirm-pass' /></p>
			";		
		}else if($type == 'multi'){
			$multi_object = new $field();
			$accumulator = $accumulated = $multi_val = "";
			$accumulator_id = $field."_accumulator_new";
			$multi_input_id = $name."_new";
			$multi_class = "multi-select-new";
			$js_parameters = "$accumulator_id,$multi_input_id";
			if($this->current_template == 'edit'){
				$results_array = "\$this->edit_{$this->class_name}->{$multi_object->table_name}";
				$accumulator_id = $field."_accumulator_edit";
				$multi_input_id = $name."_edit";
				$create_url = "/{$this->table->table_name}/add_{$field}/";
				$delete_url = "/{$this->table->table_name}/delete_{$field}/";
				$parent_id = "{$this->class_name}_{$this->table->key}";
				$multi_class = "multi-select-edit";
				$js_parameters = "$accumulator_id,$multi_input_id,$create_url,$delete_url,$parent_id";
				$accumulator = <<<EOD
				
			<?php
			\$multi_val = "";
			\$accumulated = "";
			if($results_array){						
				foreach($results_array as \${$field}){
					\$accumulated .= "<span class='accumulated edit'>{\${$field}->{$field}->{$attrs[2]}}<a href='#{\${$field}->{$multi_object->key}}' ></a></span>";
					if(\$multi_val != "") \$multi_val .= ",";
						\$multi_val .= \${$field}->{$field}->id;
				}
			}
			?>
EOD;
				$accumulated = "<?php echo \$accumulated; ?>";
				$multi_val = "<?php echo \$multi_val; ?>";
			}
			$input = <<<EOD
			
			<p><label for='{$field}_select'>$label</label></p>
			<p><select name='{$field}_select' title='$js_parameters' class='$multi_class'>
				<option value=''>{$this->texts->add} $label</option>
			<?php
			foreach(\$this->{$multi_object->table_name} as $$field){
				echo "<option value='".$$field->{$multi_object->key}."'>".$$field->$class."</option>";
			}
			?>
			</select></p>
$accumulator
			<p id='$accumulator_id'>$accumulated</p>
			<input type='hidden' id='$multi_input_id' name='$name' value='$multi_val'/>
			<div class='clear'></div>
EOD;
		}
		return $input;
	}
	private function create_cell($properties){
		$separator = ".' '.$".$this->class_name.'->';
		$properties = implode($separator,$properties);
		$cell = 'echo "<td>".$'.$this->class_name.'->'.$properties.'."</td>";
				';
		return $cell;
	}
}
?>