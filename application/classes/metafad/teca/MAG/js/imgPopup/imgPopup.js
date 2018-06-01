$(document).ready(function(){
    function imageMetricsRequired()
    {
        $('#samplingfrequencyunit').data('instance').setRequired().setEditable();
        $('#samplingfrequencyplane').data('instance').setRequired().setEditable();
        $('#bitpersample').data('instance').setRequired().setEditable();
        $('#photometricinterpretation').data('instance').setRequired().setEditable();
    }

    function imageMetricsOptional()
    {
        $('#samplingfrequencyunit').data('instance').setOptional().setReadOnly();
        $('#samplingfrequencyplane').data('instance').setOptional().setReadOnly();
        $('#bitpersample').data('instance').setOptional().setReadOnly();
        $('#photometricinterpretation').data('instance').setOptional().setReadOnly();
    }

    $('#imggroupID').on('change', function() {
        if ($(this).val() == '') {
            imageMetricsRequired();
            $('#samplingfrequencyunit').trigger('change');
        } else {
            imageMetricsOptional();
            $('#xsamplingfrequency').data('instance').setOptional().setReadOnly();
            $('#ysamplingfrequency').data('instance').setOptional().setReadOnly();
        }
    });

    $('#samplingfrequencyunit').on('change', function() {
        if ($(this).val() == '2' || $(this).val() == '3') {
            $('#xsamplingfrequency').data('instance').setRequired().setEditable();
            $('#ysamplingfrequency').data('instance').setRequired().setEditable();
        } else {
            $('#xsamplingfrequency').data('instance').setOptional();
            $('#ysamplingfrequency').data('instance').setOptional();
        }
    });

    $('#samplingfrequencyunit').trigger('change');
    $('#imggroupID').trigger('change');
});
