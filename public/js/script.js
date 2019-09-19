$(document).on('submit','.submitForm',function(){
	var formData = $(this).serialize();
	//alert(formData);
	 $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
		url: webUrl + '/products',
        type: "POST",
        data: formData,
        dataType: 'json',
        success:function(data){

        }

}); 
});