import {ErrorHandler, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {name as Meta, version as Version} from 'package.json';
import {environment} from 'src/environments/environment';

@Injectable({
	providedIn: 'root',
})
export class LighthouseService implements ErrorHandler {

	private Application = 'Test';
	private Key = 'df348abec3fe9d1117f4514a3281fb836cc0589b394f5d408adbbc162dbe9abc';
	private Version = '7.0';
	private Meta = 'Angular';
	private Environment = environment.production ? 'Production' : 'Development';

	constructor(private http: HttpClient) {
	}

	handleError(error: Error) {
		try {
			const message = error.message;
			const stack = error.stack;

			const Type = stack.split(':')[0];
			const File = /\s+at.+?\/([^\s]+)\s/.exec(stack)[1];
			const Line = /\s+at.+?(\d+:\d+)\)/.exec(stack)[1].replace(':', '.');
			const StacktraceArray = stack.split('\n');
			const Message = StacktraceArray.shift().split(':')[1].trim();
			const Stacktrace = StacktraceArray.join('\n');

			this.http.post('http://localhost/lighthouse/', {
				Type,
				Message,
				File,
				Line,
				Stacktrace,

				Environment: this.Environment,
				Meta,
				Version
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
					console.warn('An error has occurred!\n' + response);
				}
			}).catch(ex => {
				console.error('An error has occurred! But is not logged!', ex);
			});
		} catch (e) {
			console.error(e);
		}
		throw error;
	}
}
