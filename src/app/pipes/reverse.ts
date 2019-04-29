import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'reverse'})
export class Reverse implements PipeTransform {

	constructor() {
	}

	transform(input: string): string {
		if (input === null || input === undefined || input === '') {
			return '';
		}
		return input.split(' ').reverse().join(' ');
	}
}
