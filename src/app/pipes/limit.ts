import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'limit'})
export class Limit implements PipeTransform {

	static limit = 8;

	constructor() {
	}

	transform(input: string): string {
		if (input === null || input === undefined || input === '') {
			return '';
		}
		let outputArray = input.split(' ');
		if (outputArray.length > Limit.limit) {
			outputArray = outputArray.slice(0, Limit.limit);
			outputArray.push('...');
		}

		return outputArray.join(' ');
	}
}
