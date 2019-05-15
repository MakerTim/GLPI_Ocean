import {Component, DoCheck, ElementRef, OnInit, ViewChild} from '@angular/core';
import {NavigationEnd, Router} from '@angular/router';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {GLOBAL} from './services/global';
import {navItemListener, setNav} from './services/nav';
import {isLoggedIn, isLoggingIn, loginBasic, loginGLPI, logout as logoutFunction, sendSecureHeader} from './services/login.service';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';

@Component({
	selector: 'app-root',
	templateUrl: './app.component.html',
	styleUrls: ['./app.component.css'],
	providers: []
})
export class AppComponent implements OnInit, DoCheck {
	private static darkModeLocalStorage = 'HyvenDarkMode';

	public navigationIcons = {
		'Computer': 'fas fa-laptop',
		'TicketCreate': 'fas fa-phone',
		'settings': 'fas fa-wrench'
	};

	@ViewChild('loginModal') loginModalRef: ElementRef;
	public objectKeys = Object.keys;
	public navigationItems = {
		ticket: {
			TicketCreate: 'concrete/Ticket'
		}
	};
	public darkModeEnabled = false;
	public loggedIn = false;
	public loggingIn = false;
	public error = '';
	public navigation = '';

	constructor(
		private router: Router,
		private modalService: NgbModal,
		private httpClient: HttpClient) {
		router.events.forEach(event => {
			if (event instanceof NavigationEnd) {
				this.onNavChange(event.urlAfterRedirects);
			}
		});
	}

	iconOf(navItem: any): string {
		for (const key of this.objectKeys(this.navigationIcons)) {
			if (Object.keys(this.navigationItems[navItem]).indexOf(key) >= 0) {
				return this.navigationIcons[key];
			}
		}
		return 'fas fa-paint-brush';
	}

	ngOnInit() {
		const thiz = this;
		this.ngDoCheck();
		this.checkForDarkMode();

		loginGLPI(this.httpClient, () => {
			thiz.ngDoCheck();
		});
		sendSecureHeader((headers: HttpHeaders) => {
			thiz.httpClient.get(GLOBAL.api + '/Navigation', {headers}).toPromise()
				.then(nav => setNav({...thiz.navigationItems, ...nav}));
		});
		navItemListener((nav: any) => {
			thiz.navigationItems = nav;
		});
		if (!isLoggedIn()) {
			setTimeout(() => {
				this.openLogin();
			}, 1000);
		}
	}

	onNavChange(newUrl: string) {
		this.navigation = newUrl;
	}

	contains(x: string, contains: string) {
		return x.toLowerCase().indexOf(contains.toLowerCase()) >= 0;
	}

	checkLogin(modal) {
		if (isLoggedIn()) {
			modal.close();
		}
		return true;
	}

	isLoggingin(selfCheck = true) {
		if (selfCheck) {
			return this.loggingIn || isLoggingIn();
		} else {
			return isLoggingIn();
		}
	}

	openLogin() {
		this.startAnimationLogginin();
		this.modalService.open(this.loginModalRef, {centered: true}).result
			.then(() => {
			})
			.catch(() => {
				this.openLogin();
			});
	}

	startAnimationLogginin() {
		setTimeout(() => {
			const rects = document.getElementsByTagName('rect');
			let i = 0;
			// @ts-ignore
			for (const rect of rects) {
				rect.style.animation = '2s ease ' + ((i++) / 10) + 's infinite normal none running hideshow';
			}
		}, 100);
	}

	startLogin(username: string, password: string, modal: any) {
		if (isLoggedIn()) {
			modal.close();
			return;
		}
		if (!username || !password ||
			!username.trim() || !password.trim()) {
			return;
		}
		this.startAnimationLogginin();
		this.login(username, password, (error) => {
			if (!error) {
				modal.close();
			}
		});
	}

	ngDoCheck() {
		this.loggedIn = isLoggedIn();
	}

	saveI18n(input: string) {
		return input.substr(0, 1).toLocaleLowerCase() + input.substr(1).replace(/\s+/g, '');
	}

	checkForDarkMode() {
		const oldSetting = localStorage.getItem(AppComponent.darkModeLocalStorage);
		if (oldSetting == null) {
			return;
		}
		this.onToggleDarkMode(oldSetting === String(true));
	}

	onToggleDarkMode(isDark: boolean) {
		localStorage.setItem(AppComponent.darkModeLocalStorage, String(isDark));
		this.darkModeEnabled = isDark;
		const body = document.querySelector('body');
		if (isDark) {
			body.classList.add('dark');
		} else {
			body.classList.remove('dark');
		}
	}

	login(username: string, password: string, callback) {
		if (this.error || this.loggingIn) {
			return;
		}
		const thiz = this;
		this.loggingIn = true;

		loginBasic(this.httpClient, username, password, error => {
			thiz.loggingIn = false;
			thiz.showError(error);
			if (callback) {
				callback(error);
			}
		});
	}

	showError(newError = '') {
		this.error = newError;
		setTimeout(() => {
			this.error = '';
		}, 10000);
	}

	logout() {
		logoutFunction();
		this.openLogin();
	}
}
