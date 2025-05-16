(function ($) {
    "use strict";

    // MAterial Date picker
    $("#mdate").bootstrapMaterialDatePicker({
        format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
    });
    $("#mdate2").bootstrapMaterialDatePicker({
        format: "dd-MMMM-YYYY",
        weekStart: 0,
        time: false,
    });
    $("#mdate3").bootstrapMaterialDatePicker({
        format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
    });
    $("#mdate4").bootstrapMaterialDatePicker({
        format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
    });
    $("#mdate5").bootstrapMaterialDatePicker({
        format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
        minDate: new Date(),
    });
    $(".mdate-custom").bootstrapMaterialDatePicker({
        format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
        // defaultDate: new Date(),
    });
    $(".mdate-custom2").bootstrapMaterialDatePicker({
        // format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
        // defaultDate: new Date(),
    });

    $(".mdate-custom3").bootstrapMaterialDatePicker({
        format: "DD-MMMM-YYYY",
        weekStart: 0,
        time: false,
        defaultDate: new Date(),
    });

    $("#timepicker").bootstrapMaterialDatePicker({
        format: "HH:mm",
        time: true,
        date: false,
    });
    $("#date-format").bootstrapMaterialDatePicker({
        format: "dddd DD MMMM YYYY - HH:mm",
    });
    $("#date-formatEdit").bootstrapMaterialDatePicker({
        format: "dddd DD MMMM YYYY - HH:mm",
    });

    $("#min-date").bootstrapMaterialDatePicker({
        format: "YYYY-MM-DD HH:mm",
        minDate: new Date(),
    });

    $("#min-date2").bootstrapMaterialDatePicker({
        format: "YYYY-MM-DD HH:mm",
        minDate: new Date(),
    });

    $("#min-date3").bootstrapMaterialDatePicker({
        format: "YYYY-MM-DD HH:mm",
        minDate: new Date(),
    });

    // Disable input edit member registration
    $(document).ready(function () {
        var input1 = $("#input1");
        var input2 = $("#input2");

        input1.on("change", function () {
            if (input1.val() !== "") {
                input2.css("display", "none");
                $("#parentInput2").css("display", "none");
            } else {
                input2.css("display", "block");
            }
        });

        input2.on("change", function () {
            if (input2.val() !== "") {
                input1.css("display", "none");
                $("#parentInput1").css("display", "none");
            } else {
                input1.css("display", "block");
            }
        });
    });
})(jQuery);

$(document).on("click", ".member-button", function () {
    var memberName = $(this).data("name");
    var memberPhone = $(this).data("phone");
    $("#fullNameInput").val(memberName);
    $("#phoneInput").val(memberPhone);
    $("#memberModal").modal("hide");
});
