export function mappingFinder(stringIn: string, delimeter: string, toFind: string = null) {
	const explode = stringIn.split(delimeter);
	if (toFind) {
		for (const thing of explode) {
			if (thing.match(toFind)) {
				return thing.replace(toFind, '');
			}
		}
		return '';
	}
	return explode;
}

export function setToStorage(key: string, value: string = null) {
	if (value === null) {
		window.localStorage.removeItem(key);
	} else {
		window.localStorage.setItem(key, value);
	}
}

export function getFromStorage(key: string, fallback: string) {
	const storageItem = window.localStorage.getItem(key);
	if (storageItem === null) {
		return fallback;
	}
	return storageItem;
}

export function getURLParam(param: string, url = window.location.href) {
	const urlObj = new URL(url);
	return urlObj.searchParams.get(param);
}

export function getDifferences<T>(originalArray: T[], newArray: T[]) {
	const added = [];
	const removed = [];
	const notChanged = [];

	for (const originalItem of originalArray) {
		if (!newArray.includes(originalItem)) {
			removed.push(originalItem);
		} else {
			notChanged.push(originalItem);
		}
	}
	for (const newItem of newArray) {
		if (!originalArray.includes(newItem)) {
			added.push(newItem);
		}
	}

	return [added, removed, notChanged];
}
