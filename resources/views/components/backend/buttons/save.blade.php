@props(["small"=>""])
{{ html()->submit($text = icon('fas fa-save')." Save")->class('btn btn-success m-1 btnsave'.(($small=='true')? ' btn-sm' : '')) }}