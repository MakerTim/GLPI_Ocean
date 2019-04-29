import {Pipe, PipeTransform} from '@angular/core';

import {GLOBAL} from '../services/global';
// @ts-ignore
import * as jsonFile from '../../assets/i18n.json';

@Pipe({name: 'i18n'})
export class I18n implements PipeTransform {

	public static i18nTranslated: any;

	constructor() {
		I18n.i18nTranslated = jsonFile.default;
	}

	private static get(dictionary: Array<Array<string>>, lang: string, key: string): string | null {
		if ((!(lang in dictionary)) || (!(key in dictionary[lang]))) {
			return null;
		}

		return dictionary[lang][key];
	}


	public static resolvePlus(input: string, dictionary: Array<Array<string>> = this.i18nTranslated): string {
		if (input == null) {
			input = '';
		}
		const args = input.split(' ');
		input = args.shift();
		return I18n.resolve(input, args, dictionary);
	}

	public static resolve(key: string, args: string[] = [], dictionary: Array<Array<string>> = this.i18nTranslated): string {
		let translatedString = I18n.get(dictionary, GLOBAL.lang, key);
		if (translatedString == null) {
			translatedString = I18n.get(dictionary, 'default', key);
		}
		if (translatedString == null) {
			translatedString = key;
			if (args.length === 0) {
				// console.warn('Can\'t find translation for \'' + key + '\'');
			} else {
				for (let i = 0; i < args.length; i++) {
					translatedString += ' ' + args[i];
				}
				// console.warn('Can\'t find translation for \'' + key + '\' with arguments: ' + args.toString());
			}
		} else {
			for (let i = 0; i < args.length; i++) {
				translatedString = translatedString.replace('{' + i + '}', args[i]);
			}
		}
		return translatedString;
	}

	transform(input: string): string {
		return I18n.resolvePlus(input);
	}
}
