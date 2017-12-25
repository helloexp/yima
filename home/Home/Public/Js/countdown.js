//倒计时 start
var CountDowner = {
    countdown: 0,
    frequency: 1000, //频率 （毫秒为单位）
    countdownerFlag: null,
    /**
     * 初始化
     * @param countdown int 倒计时 (默认3)
     * @param frequency int 修改频率(默认1000毫秒)
     */
    initCountdown: function (countdown, frequency) {
        this.clearCountdownInterval();
        this.countdown = countdown || 5;//倒计时时间
        this.frequency = frequency || 1000;// 1s修改一次
    },
    startCountdown: function () {
        var countdownerObj = this;
        if (this.countdown > 0) {
            this.countdownerFlag = setTimeout(function () {
                countdownerObj.countdown--;
                countdownerObj.startCountdown();
            }, this.frequency);
        } else {
            clearTimeout(this.countdownerFlag);
        }
    },
    isCountdowning: function () {
        return this.countdown > 0;
    },
    clearCountdownInterval: function () {
        if (!this.isCountdowning() && this.countdownerFlag) {
            clearInterval(this.countdownerFlag);
        }
    },
    notCountdowningAndInit: function (start, countdown, frequency) {
        if (!this.isCountdowning()) {
            this.initCountdown(countdown, frequency);
            if (typeof start == 'undefined') {
                start = false;
            }
            if (start) {
                this.startCountdown();
            }
        }
    },
    notCountdowningAndInitAndStart: function (countdown, frequency) {
        this.notCountdowningAndInit(true,countdown, frequency);
    }
};
//倒计时 end