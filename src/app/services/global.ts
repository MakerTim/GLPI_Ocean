export const GLOBAL = {
	kaceUrl: 'http://kace/',
	url: 'http://kace/',
	api: 'http://192.168.12.139/glpi/plugins/ocean/api/redirect.php?redirect=http://kace/api',
	ams: 'http://192.168.12.139/glpi/plugins/ocean/api/redirect.php?redirect=http://kace/ams',
	urlInternal: document.getElementsByTagName('base')[0].href,
	lang: localStorage.getItem('locale')
		|| languageFromString(navigator.language),
	base64Encode: function (input: string) {
		return btoa(input).replace(/=/g, '');
	},
	base64Decode: function (input: string) {
		return atob(input);
	}
};

const supportedLanguages = ['nl', 'en'];

function languageFromString(navigatorLanguage: string): string {
	navigatorLanguage = navigatorLanguage.substr(0, 2);

	let selectedLanguage = 'nl';
	for (const language in supportedLanguages) {
		if (language.toLocaleLowerCase() === navigatorLanguage.toLocaleLowerCase()) {
			selectedLanguage = language;
		}
	}

	return selectedLanguage;
}
