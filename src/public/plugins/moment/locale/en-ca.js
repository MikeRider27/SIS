//! moment.js locale configuration
//! locale : English (Canada) [en-ca]
//! author : Jonathan Abourbih : https://github.com/jonbca

(function (global, factory) {
  typeof exports === "object" &&
  typeof module !== "undefined" &&
  typeof require === "function"
    ? factory(require("../moment"))
    : typeof define === "function" && define.amd
    ? define(["../moment"], factory)
    : factory(global.moment);
})(this, function (moment) {
  "use strict";

  //! moment.js locale configuration

  var enCa = moment.defineLocale("en-ca", {
    months:
      "Enero_Febrero_Marzo_Abril_Mayo_Junio_Julio_Agosto_Septiembre_Octubre_Noviembre_Diciembre".split(
        "_"
      ),
    monthsShort: "Ene_Feb_Mar_Abr_May_Jun_Jul_Ago_Sep_Oct_Nov_Dic".split("_"),
    weekdays: "Domingo_Lunes_Martes_Miercoles_Jueves_Viernes_Sabado".split("_"),
    weekdaysShort: "Dom_Lun_Mar_Mie_Jue_Vie_Sab".split("_"),
    weekdaysMin: "Do_Lu_Ma_Mi_Ju_Vi_Sa".split("_"),
    longDateFormat: {
      LT: "h:mm A",
      LTS: "h:mm:ss A",
      L: "YYYY-MM-DD",
      LL: "MMMM D, YYYY",
      LLL: "MMMM D, YYYY h:mm A",
      LLLL: "dddd, MMMM D, YYYY h:mm A",
    },
    calendar: {
      sameDay: "[Today at] LT",
      nextDay: "[Tomorrow at] LT",
      nextWeek: "dddd [at] LT",
      lastDay: "[Yesterday at] LT",
      lastWeek: "[Last] dddd [at] LT",
      sameElse: "L",
    },
    relativeTime: {
      future: "in %s",
      past: "%s ago",
      s: "a few seconds",
      ss: "%d seconds",
      m: "a minute",
      mm: "%d minutes",
      h: "an hour",
      hh: "%d hours",
      d: "a day",
      dd: "%d days",
      M: "a month",
      MM: "%d months",
      y: "a year",
      yy: "%d years",
    },
    dayOfMonthOrdinalParse: /\d{1,2}(st|nd|rd|th)/,
    ordinal: function (number) {
      var b = number % 10,
        output =
          ~~((number % 100) / 10) === 1
            ? "th"
            : b === 1
            ? "st"
            : b === 2
            ? "nd"
            : b === 3
            ? "rd"
            : "th";
      return number + output;
    },
  });

  return enCa;
});
