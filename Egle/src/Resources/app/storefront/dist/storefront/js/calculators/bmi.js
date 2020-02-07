$(document).ready(function() {
    $(function() {

        $('#weight').on('keyup', function(e) {
            e.preventDefault();
            $("#bmi-adult").html(calcBMI().replace('.', ','));
            $("#bmi-class").html(bmiState());
        });
        $('#size').on('keyup', function(e) {
            e.preventDefault();
            $("#bmi-adult").html(calcBMI().replace('.', ','));
            $("#bmi-class").html(bmiState());
        });

        function calcBMI() {
            var weight = $("#weight").val();
            var height = $("#size").val().replace(',', '.');
            var bmi    = weight / (height * height);
            return bmi.toFixed(1);
        }

        function bmiState() {
            if (calcBMI() > 30) {
                return "Übergewicht, Sie sollten mit Hilfe einer Ernährungsberatung Ihre Ernährung umstellen!";
            }
            if (calcBMI() > 25) {
                return "Übergewicht. Befinden Sie sich tatsächlich noch in Ihrem Wohlfühlgewicht? Wenn nicht, achten Sie ein wenig auf Ihr Gewicht!";
            }
            if (calcBMI() > 18.5) {
                return "Normalgewicht   :-)";
            }
            if (calcBMI() < 18.49) {
                return "Untergewicht. Befinden Sie sich tatsächlich noch in Ihrem Wohlfühlgewicht? Sie dürfen ruhig ein wenig mehr essen!";
            }
        }
    });
});