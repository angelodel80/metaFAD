function MetaDateUtils() {
    this.padLeft = function (str, chr, length) {
        str = str !== null ? str + "" : "";
        chr = chr !== null ? chr + "" : "";

        if (length <= 0) {
            return str;
        } else {
            var composite = (chr + "").repeat(length) + str;
            return composite.substr(composite.length - length);
        }
    };

    this.encodeDateYMD = function (dateObj) {
        var
            y = dateObj.year,
            m = this.padLeft(dateObj.month, "0", 2),
            d = this.padLeft(dateObj.day, "0", 2);

        if (y + "" !== "0" && !y) {
            return "";
        } else if ((m + "").length == 0 || m <= 0) {
            return y;
        } else if ((d + "").length == 0 || d <= 0) {
            return y + "/" + m;
        } else {
            return y + "/" + m + "/" + d;
        }
    };

    this.encodeDate = function (dateObj) {
        var
            y = dateObj.year,
            m = this.padLeft(dateObj.month, "0", 2),
            d = this.padLeft(dateObj.day, "0", 2);

        if (y + "" !== "0" && !y) {
            return "";
        } else if ((m + "").length == 0 || m <= 0) {
            return y + "0101-" + y + "1231";
        } else if ((d + "").length == 0 || d <= 0) {
            return y + m + "01-" + y + m + MetaDateUtils.monthDays[parseInt(m)];
        } else {
            return y + m + d;
        }
    };

    this.parseDate = function (date) {
        var results = {};
        for (var k in MetaDateUtils.regexs) {
            results[k] = MetaDateUtils.regexs[k].test(date);
        }

        var isYMD = results.ymd || results.ym || results.y;
        var isDMY = results.dmy || results.my || results.y;

        var dayPivot = results.ymd ? 2 : (results.dmy ? 0 : undefined); //Dmy (0) o ymD (2)
        var yearPivot = results.dmy && !results.ymd ? 2 : (results.my ? 1 : 0); //dmY (2) o mY (1) o Y* (0)
        var monthPivot = results.my ? 0 : (!results.y ? 1 : undefined); //My (0) o *M* (1)

        var ret = null;
        if (isYMD) {
            ret = parseCorrectDate(date, yearPivot, monthPivot, dayPivot);
        } else if (isDMY) {
            ret = parseCorrectDate(date, yearPivot, monthPivot, dayPivot);
            ret.error = "È stato rilevato il formato GG/MM/AAAA, tale data verrà convertita ad AAAA/MM/GG";
        } else {
            ret = {
                "error": "Data nel formato errato, usare il formato AAAA/MM/GG con MM e GG facoltativi.",
                "year": null,
                "month": null,
                "day": null,
                "AD": true
            };
        }

        ret.month = (ret.month != null && (ret.month + "").length == 1) ? "0" + ret.month : ret.month;
        ret.day = (ret.day != null && (ret.day + "").length == 1) ? "0" + ret.day : ret.day;

        return ret;
    };

    function parseCorrectDate(date, yPos, mPos, dPos) {
        var spaceSplit = date.split(" ");
        var dateNumbers = spaceSplit[0];
        var data = dateNumbers.split(new RegExp("[./-]"));

        var year = (yPos !== undefined && data[yPos] !== undefined) ? data[yPos] : null;
        var month = (mPos !== undefined && data[mPos] !== undefined) ? data[mPos] : null;
        var day = (dPos !== undefined && data[dPos] !== undefined) ? data[dPos] : null;
        var error = "";
        var AD = spaceSplit[1] != "a.C.";

        var yearVal = year != null ? parseInt(year) : null;
        var monthVal = month != null ? parseInt(month) : null;
        var dayVal = day != null ? parseInt(day) : null;

        function nonBisestile() {
            return (MetaDateUtils.monthDays[monthVal] < dayVal || (monthVal == 2 && yearVal % 4 != 0 && dayVal > 28));
        }

        if (yearVal != null && yearVal < 0) {
            error += "Anno negativo; ";
            yearVal = null;
            monthVal = null;
            dayVal = null;
        } else if (monthVal != null && (monthVal <= 0 || monthVal > 12)) {
            error += "Mese non tra 1 e 12; ";
            monthVal = null;
            dayVal = null;
        } else if (dayVal != null && (dayVal <= 0 || nonBisestile())) {
            error += "Giorno non compatibile con mese ed anno: non esiste il " + day + "/" + month + "/" + year + "; ";
            dayVal = null;
        }

        return {
            "error": error,
            "year": yearVal,
            "month": monthVal,
            "day": dayVal,
            "AD": AD
        };
    }


}

MetaDateUtils.monthDays = {
    1: 31,
    2: 29,
    3: 31,
    4: 30,
    5: 31,
    6: 30,
    7: 31,
    8: 31,
    9: 30,
    10: 31,
    11: 30,
    12: 31
};

MetaDateUtils.regexs = (function () {
    var y = "(0|([1-9][0-9]*))";
    var m = "((0?[1-9])|([1-9][0-9]?))";
    var d = m;
    var S = "[./-]";
    return {
        ymd: new RegExp("^" + y + S + m + S + d + "$"),
        ym: new RegExp("^" + y + S + m + "$"),
        y: new RegExp("^" + y + "$"),
        dmy: new RegExp("^" + d + S + m + S + y + "$"),
        my: new RegExp("^" + m + S + y + "$")
    }
})();