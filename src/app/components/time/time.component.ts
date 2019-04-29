import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {GLOBAL} from '../../services/global';
import {DomSanitizer} from '@angular/platform-browser';
import {I18n} from '../../pipes/translator';

@Component({
	selector: 'time',
	templateUrl: './time.component.html',
	styleUrls: ['./time.component.scss']
})
export class TimeComponent implements OnInit, OnDestroy {
	private static timeNames = ['time.now', 'time.second', 'time.minute', 'time.hour', 'time.day', 'time.week', 'time.month', 'time.year'];
	public static zoneInSeconds = [/*     */1, /*         */60, /*        */3600, /*    */86400, /*  */604800, /*  */2419200, /*  */29030400];
	public static timePrecise = 1;

	static timeComponents: TimeComponent[];
	private static localTimeOffset: DateOffset;

	private static setup(http: HttpClient) {
		if (this.timeComponents) {
			return;
		}
		this.calculateTimeDiff(http);

		this.timeComponents = [];
		setInterval(() => {
			this.timeComponents.forEach(comp => comp.update());
		}, this.timePrecise * 1000);
	}

	protected static calculateTimeDiff(http: HttpClient) {
		const localDate = new Date();
		http.get(GLOBAL.url + '/api/time.php').toPromise()
			.then(answer =>
				this.localTimeOffset =
					DateOffset.timeDifference(localDate, new Date(answer['now']))
			);
	}

	@Input() format: 'full' | 'short' = 'short';
	@Input() time: Date = new Date();
	@Input() allowFuture = false;
	@Input() class: string;
	public output: string;
	public readableTime = '';

	constructor(
		private http: HttpClient) {
		TimeComponent.setup(http);
	}

	ngOnInit() {
		if ((typeof this.time) == 'string') {
			this.time = new Date(this.time);
		}
		this.fixServerTimeOffset();
	}

	ngOnDestroy() {
		this.unregister();
	}

	fixServerTimeOffset(fallback: boolean = false) {
		if (TimeComponent.localTimeOffset == null) {
			if (!fallback) {
				setTimeout(() => this.fixServerTimeOffset(true), 1000);
			}
			return;
		}
		const millisOffset = TimeComponent.localTimeOffset.seconds * 1000;
		this.time = new Date(this.time.getTime() - millisOffset);

		this.setReadableTime();
		this.register();
		this.update();
	}

	setReadableTime() {
		this.readableTime = this.time.toLocaleString(GLOBAL.lang, {
			'timeZone': 'Etc/GMT-1',
			'weekday': 'long',
			'year': 'numeric',
			'month': 'long',
			'day': '2-digit',
			'hour': '2-digit',
			'minute': '2-digit',
			'second': '2-digit',
		});
	}

	update(fallback: boolean = false): void {
		const timeArrayObj = DateOffset.timeDifference(this.time, new Date()); // maybe date and time wrong way around
		this.output = '';

		if (timeArrayObj.isFuture() && !this.allowFuture) {
			this.output = I18n.resolve(TimeComponent.timeNames[0] + '.' + this.format);
			return;
		}
		let n = 0;
		const timeArray = timeArrayObj.toTimeArray();
		for (let i = timeArray.length - 1; i >= 0; i--) {
			let timeName = TimeComponent.timeNames[i + 1];
			if (timeArray[i] > 0) {
				if (timeArray[i] > 1) {
					timeName += 's';
				}
				if (n > 0) {
					this.output += I18n.resolve('time.separator');
				}
				this.output += I18n.resolve(timeName + '.' + this.format, [timeArray[i].toString()]);
				n++;
				if (n == 2) {
					break;
				}
			}
		}
		if (timeArrayObj.isFuture()) {
			this.output += ' ' + I18n.resolve('time.ago');
		}
	}

	register() {
		TimeComponent.timeComponents.push(this);
	}

	unregister() {
		const index = TimeComponent.timeComponents.indexOf(this);
		if (index >= 0) {
			TimeComponent.timeComponents.splice(index, 1);
		}
	}
}

class DateOffset {
	public seconds = 0;

	static timeDifference(timeA: Date, timeB: Date): DateOffset {
		return new DateOffset((timeA.getTime() - timeB.getTime()) / 1000);
	}

	public constructor(seconds: number) {
		this.seconds = Math.round(seconds / TimeComponent.timePrecise) * TimeComponent.timePrecise;
	}

	public isFuture(): boolean {
		return this.seconds >= 0;
	}

	public offset(offset: DateOffset): DateOffset {
		return new DateOffset(this.seconds - offset.seconds);
	}

	public addToDate(date: Date = new Date()): Date {
		let time = date.getTime();
		const timeArray = this.toTimeArray();

		timeArray.forEach((val, i) => {
			time += timeArray[i] * TimeComponent.zoneInSeconds[i];
		});

		return new Date(time);
	}

	public toTimeObject(): any {
		const timeArray = this.toTimeArray();
		return {
			seconds: timeArray[0],
			minutes: timeArray[1],
			hours: timeArray[2],
			days: timeArray[3],
			weeks: timeArray[4],
			months: timeArray[5],
			years: timeArray[6],
		};
	}

	public toTimeArray(): Array<number> {
		const retArray = [0, 0, 0, 0, 0, 0, 0];
		let seconds = Math.abs(this.seconds);

		let n = 0;
		for (let i = retArray.length - 1; i >= 0; i--) {
			if (seconds >= TimeComponent.zoneInSeconds[i]) {
				retArray[i] = Math.floor(seconds / TimeComponent.zoneInSeconds[i]);
				seconds -= retArray[i] * TimeComponent.zoneInSeconds[i];
				if (n === 0) {
					n = i;
				}
			}
			if (i < n) {
				break;
			}
		}
		return retArray;
	}
}
