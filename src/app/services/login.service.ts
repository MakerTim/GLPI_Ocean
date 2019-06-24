import {HttpClient, HttpHeaders} from '@angular/common/http';
import {getFromStorage, getURLParam, setToStorage} from './mappingUtil';
import {GLOBAL} from './global';
import {SelfRequest} from '../models/User';

// @ts-ignore, always string
let personalToken = getFromStorage('personal-token', '');
const callbackList = [];

let client: HttpClient;
let user: Promise<SelfRequest> = null;
let rawUser = null;
let loggingIn = false;
let loginError = '';

export function loginKace(httpClient: HttpClient, callback = null) {
	client = httpClient;
	waitForUser();
	if (!isLoggedIn()) {
		callbackList.push(callback);
		tryAutoLogin(httpClient);
		return;
	}
	if (callback && (typeof callback) === 'function') {
		callback();
	}
}

function waitForUser() {
	sendSecureHeader(headers => {
		user = client.get<SelfRequest>(GLOBAL.api + '/users/me/', {headers, withCredentials: true}).toPromise();
		user
			.then(usr => rawUser = usr)
			.catch(_ => {
				logout();
				window.location.reload();
			});
	});
}

export function logout() {
	setToStorage('personal-token');
	personalToken = '';
	user = null;
	waitForUser();
}

export function isLoggingIn() {
	return loggingIn;
}

export function isLoggedIn() {
	return personalToken.length !== 0;
}

export function getUser(callback: (user: SelfRequest) => void) {
	sendSecureHeader(_ => {
		user.then(callback);
	});
}

export function getUserRaw() {
	return rawUser;
}

export function sendSecureHeader(callback: (headers: HttpHeaders) => void, header: HttpHeaders = new HttpHeaders) {
	if (!callback || (typeof callback) !== 'function') {
		console.error('Callback should be function ', callback);
		return;
	}
	header = header.set('x-dell-csrf-token', personalToken)
		.set('Accept', 'application/json')
		.set('Content-Type', 'application/json')
		.set('x-dell-api-version', '1');
	if (!isLoggedIn()) {
		callbackList.push(() => {
			callback(header);
		});
		return;
	}
	callback(header);
}

function setPersonalToken(token: string) {
	personalToken = token;
	setToStorage('personal-token', token);

	for (const callback of callbackList) {
		try {
			callback();
		} catch (e) {
			console.warn('Callback loginservice error: ', e);
		}
	}
	loggingIn = false;
	callbackList.length = 0;
}

export function loginBasic(httpClient: HttpClient, userName: string, password: string, callbackError: (error: string) => void) {
	if (userName.length === 0) {
		callbackError('login.noUsername');
		return;
	}
	if (password.length === 0) {
		callbackError('login.noPassword');
		return;
	}
	loggingIn = true;
	httpClient.post(GLOBAL.ams + '/shared/api/security/login', {
		userName,
		password
	}, {
		headers: {
			'Accept': 'application/json',
			'Content-Type': 'application/json',
			'x-dell-api-version': '1',
		},
		withCredentials: true,
		observe: 'response'
	}).toPromise()
		.then(response => {
			console.log(response.headers.get('x-dell-csrf-token'));
			setPersonalToken(response.headers.get('x-dell-csrf-token'));
			callbackError('');
		})
		.catch(err => {
			callbackError('login.failed ' + GLOBAL.base64Encode(err.error));
			loggingIn = false;
		});
}

export function getLoginError() {
	return loginError;
}

function tryAutoLogin(httpClient: HttpClient) {
	// Try SID login
	const sid = getURLParam('Sid');
	if (sid) {
		loggingIn = true;
		httpClient.get(GLOBAL.api + '/Login', {
			headers: {
				'AD-SID': sid
			}
		}).toPromise().then(response => {
			setPersonalToken(response['personal-token']);
		}).catch(response => {
			loggingIn = false;
			loginError = response.error;
		});
	}
}
