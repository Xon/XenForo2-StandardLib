// noinspection ES6ConvertVarToLetConst
var SV = window.SV || {};
SV.StandardLib = SV.StandardLib || {};
SV.$ = SV.$ || window.jQuery || null;

(function()
{
    "use strict";
    var $ = SV.$;

    if (typeof moment === 'function')
    {
        /**
         * @see https://gist.github.com/phpmypython/f97c5f5f59f2a934599d
         */
        (function ()
        {
            /*
             * PHP => moment.js
             * Will take a php date format and convert it into a JS format for moment
             * http://www.php.net/manual/en/function.date.php
             * http://momentjs.com/docs/#/displaying/format/
             */
            let formatMap = {
                    d: 'DD',
                    D: 'ddd',
                    j: 'D',
                    l: 'dddd',
                    N: 'E',
                    S: function () {
                        return '[' + this.format('Do').replace(/\d*/g, '') + ']';
                    },
                    w: 'd',
                    z: function () {
                        return this.format('DDD') - 1;
                    },
                    W: 'W',
                    F: 'MMMM',
                    m: 'MM',
                    M: 'MMM',
                    n: 'M',
                    t: function () {
                        return this.daysInMonth();
                    },
                    L: function () {
                        return this.isLeapYear() ? 1 : 0;
                    },
                    o: 'GGGG',
                    Y: 'YYYY',
                    y: 'YY',
                    a: 'a',
                    A: 'A',
                    B: function () {
                        var thisUTC = this.clone().utc(),
                            // Shamelessly stolen from http://javascript.about.com/library/blswatch.htm
                            swatch = ((thisUTC.hours() + 1) % 24) + (thisUTC.minutes() / 60) + (thisUTC.seconds() / 3600);

                        return Math.floor(swatch * 1000 / 24);
                    },
                    g: 'h',
                    G: 'H',
                    h: 'hh',
                    H: 'HH',
                    i: 'mm',
                    s: 'ss',
                    u: '[u]', // not sure if moment has this
                    e: '[e]', // moment does not have this
                    I: function () {
                        return this.isDST() ? 1 : 0;
                    },
                    O: 'ZZ',
                    P: 'Z',
                    T: '[T]', // deprecated in moment
                    Z: function () {
                        return parseInt(this.format('ZZ'), 10) * 36;
                    },
                    c: 'YYYY-MM-DD[T]HH:mm:ssZ',
                    r: 'ddd, DD MMM YYYY HH:mm:ss ZZ',
                    U: 'X'
                },
                formatEx = /[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]/g;

            moment.fn.formatPHP = function (format)
            {
                let self = this;

                return this.format(format.replace(formatEx, function (phpStr)
                {
                    if (typeof formatMap[phpStr] === 'function')
                    {
                        return formatMap[phpStr].call(self);
                    }

                    return formatMap[phpStr];
                }));
            };
        }());
    }

    SV.StandardLib.RelativeTimestamp = XF.Element.newHandler({
        options: {
            countUp: false,
            timestamp: null,
            dateFormat: null,
            timeFormat: null,
            triggerEvent: null,
            triggerEventOnSelector: null,
            maximumDateParts: 0,
            showSeconds: false,
        },

        field: null,
        timer: null,
        interval: 0,
        showSecondsThreshold: 2 * 60 * 1000,

        init: function()
        {
            if (!this.options.timestamp)
            {
                console.error('Timestamp is missing.');
                return;
            }

            if (!this.options.dateFormat)
            {
                console.error('Date format missing.');
                return;
            }

            if (!this.options.timeFormat)
            {
                console.error('Time format missing.');
                return;
            }

            if (typeof moment !== 'function')
            {
                console.error('Moment.js not loaded.');
                return;
            }

            // noinspection JSUnresolvedReference
            this.field = this.target || this.$target.get(0);

            const now = Math.floor(Date.now() / 1000) * 1000,
                end = this.options.timestamp * 1000;
            this.updateTimer(now, end);
        },

        updateTimer(now, end) {
            let interval = Math.abs(now - end) <= this.showSecondsThreshold ? 1000 : 10000;
            if (this.interval !== interval) {
                clearInterval(this.timer);
                this.timer = setInterval(this.updateTime.bind(this), interval);
                this.interval = interval;
            }
        },

        updateTime: function ()
        {
            const now = Math.floor(Date.now() / 1000) * 1000,
                end = this.options.timestamp * 1000;

            if (now <= end)
            {
                this.handleCountDown(now, end);
            }
            else if (this.options.countUp)
            {
                this.handleCoupUp(end, now);
            }
            else
            {
                this.handleCountDownEnd(end);
            }

            this.updateTimer(now, end);
        },

        handleCountDown: function (nowTimestamp, endTimestamp)
        {
            this.handleTimeStrOutput(this.getTimeStr(moment.duration(endTimestamp - nowTimestamp, 'milliseconds')));
        },

        handleCoupUp: function (endTimestamp, nowTimestamp)
        {
            this.handleTimeStrOutput(this.getTimeStr(moment.duration(nowTimestamp - endTimestamp, 'milliseconds')))
        },

        handleCountDownEnd: function (endTimestamp)
        {
            this.clearTimer();

            let momentObj = moment.unix(endTimestamp / 1000),
                fullEnd = this.getPhrase('date_x_at_time_y', {
                    '{date}': momentObj.formatPHP(this.options.dateFormat),
                    '{time}': momentObj.formatPHP(this.options.timeFormat)
                });

            if (!fullEnd)
            {
                console.error('Unable to get full end date.');
                return;
            }

            this.field.textContent = fullEnd;
        },

        /**
         *
         * @param {string|null} timeStr
         */
        handleTimeStrOutput: function (timeStr)
        {
            if (typeof timeStr === "string")
            {
                if (this.field.textContent !== timeStr) {
                    this.field.textContent = timeStr;
                }
            }
            else
            {
                this.handleCountDownEnd(this.options.timestamp * 1000);
            }
        },

        /**
         * @param {moment} momentObj
         *
         * @returns {string|null}
         */
        getTimeStr: function (momentObj)
        {
            let self = this,
                timeArr = [],
                showSeconds = this.options.showSeconds || momentObj.asMilliseconds() <= this.showSecondsThreshold,
                maximumDateParts = this.options.maximumDateParts;

            ['year', 'month', 'day', 'hour', 'minute', 'second'].forEach((type) => {
                if (maximumDateParts && timeArr.length >= maximumDateParts) {
                    return;
                }

                let timePartStr = self.getDatePart(momentObj, type);
                if (typeof timePartStr !== 'string') {
                    if (maximumDateParts > 0 && timeArr.length > 0) {
                        maximumDateParts = timeArr.length;
                    }
                    return;
                }

                if (!showSeconds && type === 'second'&& timeArr.length !== 0) {
                    return;
                }

                timeArr.push(timePartStr);
            });

            if (!timeArr.length)
            {
                return null;
            }

            return timeArr.join(', ');
        },

        /**
         * @param {moment} momentObj
         * @param {String} type
         */
        getDatePart: function (momentObj, type)
        {
            if (typeof type !== 'string')
            {
                console.error('Invalid date type provided.', type);
                return false;
            }

            let methodName = type + 's';
            if (typeof momentObj[methodName] !== 'function')
            {
                console.error('Invalid date type provided.', type);
                return false;
            }

            let value = parseInt(momentObj[methodName]()),
                phrase = 'svStandardLib_time.' + type + (value > 1 ? 's' : '');

            // skip zero items
            if (!value)
            {
                return false;
            }

            return this.getPhrase(phrase, {
                '{count}': value
            });
        },

        /**
         * @param {String} phrase
         * @param {Object} args
         *
         * @returns {boolean|string}
         */
        getPhrase: function (phrase, args)
        {
            args = typeof args === 'object' ? args : {};

            if (typeof phrase !== 'string' || !phrase)
            {
                this.clearTimer();

                console.error('Invalid phrase provided.', phrase);
                return false;
            }

            if (!(phrase in XF.phrases))
            {
                this.clearTimer();

                console.error('Phrase is not available.', phrase);
                return false;
            }

            let translatedValue = XF.phrase(phrase, args, null);
            if (translatedValue === null)
            {
                this.clearTimer();

                console.error('Phrase translation failed.', phrase);
                return false;
            }

            return translatedValue;
        },

        clearTimer: function ()
        {
            if (this.timer)
            {
                clearInterval(this.timer);
                this.timer = null;
                this.triggerEventIfNeeded();
            }
        },

        getEventTarget: function ()
        {
            let eventTargetSelector = this.options.triggerEventOnSelector;
            if (!eventTargetSelector)
            {
                return null; // eg: if dismiss button is not found, we do not want to click on the span itself
            }

            if (this.$target)
            {
                // jQuery
                let $result = XF.findRelativeIf(eventTargetSelector, this.target || this.$target)
                return $result.length !== 0 ? $result.get(0) : null;
            }

            return XF.findRelativeIf(eventTargetSelector, this.target);
        },

        triggerEventIfNeeded: function ()
        {
            let eventTarget = this.getEventTarget(),
                eventName = this.options.triggerEvent;

            if (!eventTarget || !eventName)
            {
                return;
            }

            if (typeof XF.trigger === "function") {
                XF.trigger(eventTarget, eventName);
            } else {
                $(eventTarget).trigger(eventName);
            }
        }
    });

    XF.Element.register('sv-standard-lib--relative-timestamp', 'SV.StandardLib.RelativeTimestamp');
})();