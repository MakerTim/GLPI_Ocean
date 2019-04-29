import {Pipe, PipeTransform} from '@angular/core';

export class MDRule {
	token: RegExp;
	isSingle: boolean;
	prefix: string;
	postfix: string;
	preProcessing: any;
	postProcessing: any;

	constructor(token: string, prefix: string, postfix: string, allowMultiline: boolean = false, isSingle: boolean = false) {
		let regexFlags = 'g';
		let all = '.'
		if (allowMultiline) {
			all = '[^]';
		}
		let pattern;
		try {
			if (isSingle) {
				pattern = '(' + token + ')';
			} else if (allowMultiline) {
				pattern = token + '(' + all + '*?)' + token;
			} else {
				pattern = token + '((?:\\S' + all + '*?\\S)|\\S)' + token;
			}
			this.token = new RegExp(pattern, regexFlags);
		} catch (ex) {
			this.token = new RegExp('');
			console.error('Regex error: ' + pattern);
			console.error(ex);
		}
		this.isSingle = isSingle;
		this.prefix = prefix;
		this.postfix = postfix;
	}

	accept(input: string): string {
		if (this.preProcessing) {
			input = this.preProcessing(input);
		}
		input = input.replace(this.token, this.prefix + (this.isSingle ? '' : '$1') + this.postfix);
		if (this.postProcessing) {
			input = this.postProcessing(input);
		}
		return input;
	}
}

@Pipe({name: 'md'})
export class MD implements PipeTransform {

	static mdDictionary: MDRule[] = function () {
		return [
			new MDRule('<', '&lt;', '', false, true),
			new MDRule('>', '&gt;', '', false, true),
			new MDRule('\\*', '<b>', '</b>'),
			new MDRule('-', '<i>', '</i>'),
			new MDRule('_', '<u>', '</u>'),
			new MDRule('\\|\\|', '<span class="spoiler">', '</span>'),
			new MDRule('~', '<s>', '</s>'),
			new MDRule('```\\n?', '<div class="quoteBlock">', '</div>', true),
			new MDRule('`', '<span class="quote">', '</span>'),
			new MDRule('\n', '<br>', '', false, true),
		];
	}();

	transform(input: string): string {
		MD.mdDictionary.forEach(rule => {
			input = rule.accept(input);
		});
		return input
	}
}