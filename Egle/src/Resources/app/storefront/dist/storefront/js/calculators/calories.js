$(document).ready(function() {
    if($('.caloriecalculator').length) {
        $(function() {
            this.weight = 0;
            this.size   = 0;
            this.age    = 0;
            this.pal    = 0;
            initValues();

            $('#weight').on('keyup', function(e) {
                initValues();
                calc();
            });
            $('#size').on('keyup', function(e) {
                initValues();
                calc();
            });
            $('#age').on('keyup', function() {
                initValues();
                calc();
            });
            $('#pal').on('keyup', function() {
                initValues();
                calc();
            });

            function initValues() {
                this.weight = $('#weight').val();
                this.size   = $('#size').val();
                this.age    = $('#age').val();
                this.pal    = $('#pal').val().replace(',', '.');
            }

            function calc() {
                var basalFemale = calcBasalFemale(this.weight, this.size, this.age);
                var dailyFemale = calcDailyFemale(basalFemale, this.pal);
                var basalMale   = calcBasalMale(this.weight, this.size, this.age);
                var dailyMale   = calcDailyMale(basalMale, this.pal);

                $('#basal-metabolic-female').html($.number(basalFemale, 2));
                $('#basal-metabolic-male').html($.number(basalMale, 2));
                $('#daily-consumption-female').html($.number(dailyFemale, 2));
                $('#daily-consumption-male').html($.number(dailyMale, 2));
            }

            function calcBasalFemale(weight, size, age) {
                return 655 + (9.6 * weight) + (1.8 * size) - (4.7 * age);
            }

            function calcBasalMale(weight, size, age) {
                return 67 + (13.7 * weight) + (5 * size) - (6.8 * age);
            }

            function calcDailyFemale(basalValue, pal) {
                return basalValue * pal;
            }

            function calcDailyMale(basalValue, pal) {
                return basalValue * pal;
            }
        });
    }
});