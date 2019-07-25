import {ErrorHandler, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';

@Injectable({
	providedIn: 'root',
})
export class LighthouseService implements ErrorHandler {

	private Application = 'KaceBox';
	private Key = '6cad43c34120c69b73c94ed8767c97bcb0c16814f8531aee0acdf0f445064486';
	private Version = '0.1';
	private Meta = 'KaceOcean';
	private Environment =
		window.location.href.indexOf('localhost') >= 0 ||
		window.location.href.indexOf('192.') >= 0 ? 'DEV' : 'PROD';

	private mouseX = 0;
	private mouseY = 0;

	constructor(private http: HttpClient) {
		window.onmousemove = evt => {
			this.mouseX = evt.clientX;
			this.mouseY = evt.clientY;
		};
	}

	handleError(error: Error) {
		try {
			const width = 5;
			const mouseOffsetX = this.mouseX + Math.ceil(width / 2);
			const mouseOffsetY = this.mouseY + Math.ceil(width / 2);
			let promise;
			// @ts-ignore
			if (typeof html2canvas === 'function') {
				// @ts-ignore
				promise = html2canvas(document.body, {logging: false});
			} else {
				promise = new Promise(resolve => resolve(null));
			}
			promise.then(canvas => {
				let Screenshot = null;
				try { // Add cursor to error
					const context = canvas.getContext('2d');
					const shadow = 2;
					const radius = 15;
					['black', 'white'].forEach((color, index) => {
						context.beginPath();
						context.strokeStyle = color;
						context.lineWidth = width - (index * shadow);
						context.arc(mouseOffsetX, mouseOffsetY, radius, 0, Math.PI / 2);
						context.moveTo(mouseOffsetX - (shadow - index), mouseOffsetY);
						context.lineTo(mouseOffsetX + radius + (shadow - index), mouseOffsetY);
						context.moveTo(mouseOffsetX, mouseOffsetY - (shadow - index));
						context.lineTo(mouseOffsetX, mouseOffsetY + radius + (shadow - index));
						context.stroke();
					});
					Screenshot = canvas.toDataURL();
				} catch (e) {
					console.log('Error with screenshot', e);
				}

				let Message = error.message;
				const stack = error.stack;

				if (Message) {
					Message = Message.split('\n')[0];
				}

				const Type = stack.split(':')[0];
				const File = /\s+at.+?\/([^\s]+)\s/.exec(stack)[1];
				const Line = /\s+at.+?(\d+:\d+)\)/.exec(stack)[1].replace(':', '.');
				const StacktraceArray = stack.split('\n');
				StacktraceArray.shift().split(':')[1].trim();
				const Stacktrace = StacktraceArray.join('\n');

				this.http.post('http://localhost/lighthouse/', {
					Type,
					Message,
					File,
					Line,
					Stacktrace,

					Environment: this.Environment,
					Meta: this.Meta,
					Version: this.Version
				}, {
					headers: {
						Application: this.Application,
						Key: this.Key,
						Incident: '1',
						'Content-Type': 'application/json' // optional
					}
				}).toPromise().then((response: { ID: string }) => {
					try {
						const id = response.ID;
						const idParsed = atob(id);
						console.warn('An error has occurred!\n   Reference id = ' + id + ' (' + idParsed + ')');
					} catch (e) {
						console.warn('An error has occurred!\n' + JSON.stringify(response));
					}
				}).catch(ex => {
					console.error('An error has occurred! But is not logged!', ex);
				});
			});
		} catch (e) {
			console.error(e);
			throw error;
		}
		throw error;
	}
}
