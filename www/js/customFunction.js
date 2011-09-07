$(document).ready(function(){
    
    $(".bloc").css("display", "none");
    $(".mine").removeClass("active");
    
    switch (focus) { 
    case 'password-recovery':
	$("#bloc-forgotten").css("display", "block");
	$("#forgotten").addClass("active");
	break; 	
	 
    case 'new-password':
	$("#bloc-forgotten-change").css("display", "block");
	$("#forgotten").addClass("active");
	break; 	

    case 'create-account':
	$("#bloc-account").css("display", "block");
	$("#account").addClass("active");
	break; 	
	
    default: 
	$("#bloc-signin").css("display", "block");
	$("#signin").addClass("active");
	break; 	
    }

    $(".mine").click(
	function() {
	    var number = 4;
	    var i = 1;

	    $(".mine").removeClass("active");
	    $(this).addClass("active");
	    var blocid = $(this).attr("id"); 
	    $(".bloc").css("display", "none"),
	    $("#bloc-" + blocid).css("display", "block");
	}
    );
   
});
