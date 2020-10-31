jQuery(function($) {
    //$('file#file_upload').on('change', function () {
		$(document).on('change','#file_upload',function(){
        $this = $(this);
        file_data = $(this).prop('files')[0];
		//console.log(file_data);
		if( typeof file_data === 'undefined' )
		{
			return false;
		}
        form_data = new FormData();
        form_data.append('file', file_data);
        form_data.append('action', 'file_upload');
 
        $.ajax({
            url: abtpg_params.abtpg_ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                $this.val('');
				var response_obj = JSON.parse(response);
				var url = response_obj.url;
				var success = response_obj.success;
				if( success )
				{
					var file_path = response_obj.file;
					$("#advance_bank_file_validation").val(url);
					$("#file_absolute_path").val(file_path);
					$(".show_file_path").html(url+"<a class='remove_uploaded_file submitdelete'>X</a>");
				}else
				{
					alert(response_obj.mssg);
				}				
            }
        });
    });   
	//$("span.remove_uploaded_file").click(function(){
	$("body").on('click','.remove_uploaded_file',function(){
		var r = confirm("Are you sure you want to delete this receipt?");
		if (r == false)
		{
			return;
		}
		var get_file_path = $("#file_absolute_path").val();
		$.ajax({
            url: abtpg_params.abtpg_ajaxurl,
            type: 'POST',
            data: form_data,
			data : {action: "unlink_uploaded_file", get_file_path : get_file_path},
            success: function (response) {
				var response_obj = JSON.parse(response);
				var success_r = response_obj.success;
				if( success_r )
				{
					$("#advance_bank_file_validation").val('');
				    $(".show_file_path").html("");
				}				
            }
        });
	});
});