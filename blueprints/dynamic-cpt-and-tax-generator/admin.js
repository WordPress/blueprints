jQuery(document).ready(function($){
    $('#dcpt_action').change(function(){
        $('#new_cpt_fields, #add_taxonomy_fields').hide();
        if($(this).val()=='new_cpt') $('#new_cpt_fields').show();
        if($(this).val()=='add_taxonomy') $('#add_taxonomy_fields').show();
    });
});
