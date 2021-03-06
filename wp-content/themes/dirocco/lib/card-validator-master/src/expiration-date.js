var parseDate = require('./parse-date');
var expirationMonth = require('./expiration-month');
var expirationYear = require('./expiration-year');
var isString = require('lodash/lang/isString');

function verification(isValid, isPotentiallyValid, month, year) {
  return {
    isValid: isValid,
    isPotentiallyValid: isPotentiallyValid,
    month: month,
    year: year
  };
}

function expirationDate(value) {
  var date, monthValid, yearValid, isValidForThisYear;

  if (!isString(value)) {
    return verification(false, false, null, null);
  }

  value = value.replace(/^(\d\d) (\d\d(\d\d)?)$/, '$1/$2');
  date = parseDate(value);
  monthValid = expirationMonth(date.month);
  yearValid = expirationYear(date.year);

  if (yearValid.isValid) {
    if (yearValid.isCurrentYear) {
      isValidForThisYear = monthValid.isValidForThisYear;
      return verification(isValidForThisYear, isValidForThisYear, date.month, date.year);
    }

    if (monthValid.isValid) {
      return verification(true, true, date.month, date.year);
    }
  }

  if (monthValid.isPotentiallyValid && yearValid.isPotentiallyValid) {
    return verification(false, true, null, null);
  }

  return verification(false, false, null, null);
}

module.exports = expirationDate;
