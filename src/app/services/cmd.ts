export function statsToJson(statsString: string) {
	const stats: any[] = statsString.split('\n');
	const output = {};
	let lastKey = '';
	let lastSubKey = '';
	let lastIndent = 100;

	for (const index in stats) {
		try {
			if (stats.hasOwnProperty(index)) {
				const stat: string = stats[index];

				let keyValue = /^([^ :][^:]+): +(.+)/gm.exec(stat);
				if (keyValue) {
					// tslint:disable-next-line
					const key: string = keyValue[1];
					let value: any = keyValue[2];
					lastSubKey = '';

					if (output.hasOwnProperty(key)) {
						let existing: any = output[key];
						if ((typeof existing) !== 'object') {
							const value2 = existing;
							existing = {};
							if (value2.toString().indexOf(':')) {
								existing[value2.toString().substring(0, value2.toString().indexOf(':'))] =
									value2.toString().substring(value2.toString().indexOf(':') + 1).trim();
							} else {
								existing[value2.toString()] = {};
							}
						}
						if (value.toString().indexOf(':')) {
							existing[value.toString().substring(0, value.toString().indexOf(':'))] =
								value.toString().substring(value.toString().indexOf(':') + 1).trim();
						} else {
							existing[value.toString()] = {};
						}
						value = existing;
					}
					output[key] = value;
					lastKey = key;
					continue;
				}

				keyValue = /\[(0?\d+)]: (.+)/gm.exec(stat);
				if (keyValue) {
					// tslint:disable-next-line
					let key = (parseInt(keyValue[1], 10) - 1).toString();
					const value = keyValue[2];

					const indent = (stat.length - stat.trim().length);
					if (indent <= lastIndent) {
						lastIndent = indent;
						lastSubKey = key;
					}
					if (indent > lastIndent) {
						key = lastSubKey + '.' + key;
					}

					if ((typeof output[lastKey]) !== 'object') {
						const previousOutput = output[lastKey].toString();
						output[lastKey] = {};
						if (previousOutput.indexOf(':') > 0) {
							output[lastKey][previousOutput.substring(0, previousOutput.indexOf(':') - 1)]
								= previousOutput.substring(previousOutput.indexOf(':')).trim();
						}
					}
					output[lastKey][key] = value;
					continue;
				}

				keyValue = /([^:]+): +(.+)/gm.exec(stat);
				if (keyValue) {
					// tslint:disable-next-line
					let key = keyValue[1].trim();
					const value = keyValue[2];

					const indent = (stat.length - stat.trim().length);
					if (indent <= lastIndent) {
						lastIndent = indent;
						lastSubKey = key;
					}
					if (indent > lastIndent) {
						key = lastSubKey + '.' + key;
					}

					if ((typeof output[lastKey]) !== 'object') {
						const previousOutput = output[lastKey].toString();
						output[lastKey] = {};
						if (previousOutput.indexOf(':') > 0) {
							output[lastKey][previousOutput.substring(0, previousOutput.indexOf(':') - 1)]
								= previousOutput.substring(previousOutput.indexOf(':')).trim();
						}
					}
					output[lastKey][key] = value;
					continue;
				}

				if (stat.length - stat.trim().length > lastIndent) {
					const key = lastSubKey + '.' + stat.trim();

					output[lastKey][key] = '';
				}
				output[stat.replace(/:/g, '').trim()] = '';
			}
		} catch (e) {
			console.error(e);
		}
	}
	return output;
}
