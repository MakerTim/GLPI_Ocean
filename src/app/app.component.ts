import {Component, OnInit} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {GLOBAL} from './GLOBAL';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {

    constructor(private http: HttpClient) {
    }

    static loggedInKey = 'report_loggedIn';

    loggingIn = false;
    loggedIn: string = localStorage.getItem(AppComponent.loggedInKey);
    user: any;

    static formatDate(d = new Date(), firstOfMonth = false, minusMonth = 0) {
        if (d == null) {
            d = new Date();
        }
        let month = (d.getMonth() + 1 - minusMonth).toString();
        let day = d.getDate().toString();
        const year = d.getFullYear().toString();

        if (month.length < 2) {
            month = '0' + month;
        }
        if (firstOfMonth) {
            day = '01';
        } else if (day.length < 2) {
            day = '0' + day;
        }

        return [year, month, day].join('-');
    }

    ngOnInit() {
        if (this.loggedIn) {
            const headers = new HttpHeaders().set('x-dell-api-version', '1').set('x-dell-csrf-token', this.loggedIn);
            this.http.get(GLOBAL.kaceURL + 'api/users/me', {headers, withCredentials: true}).subscribe((response: any) => {
                if (response.error || response.errorCode) {
                    this.loggedIn = null;
                    return;
                }
                this.user = response;
            });
        }
    }

    resetToken() {
        localStorage.removeItem(AppComponent.loggedInKey);
    }

    submit(event) {
        const userName: string = event.target[0].value;
        const password: string = event.target[1].value;

        if (this.loggingIn || !userName || !password ||
            !userName.trim() || !password.trim()) {
            return false;
        }
        this.loggingIn = true;

        const headers = new HttpHeaders().set('x-dell-api-version', '1');
        this.http.post(GLOBAL.kaceURL + 'ams/shared/api/security/login', {userName, password}, {
            headers,
            withCredentials: true,
            observe: 'response'
        })
            .subscribe(response => {
                localStorage.setItem(AppComponent.loggedInKey, this.loggedIn = response.headers.get('x-dell-csrf-token'));
                this.loggingIn = false;
                this.ngOnInit();
            });

        return false;
    }

    endOfMonth(minusMonth = 0) {
        return AppComponent.formatDate(new Date(new Date(this.formatToday(true, -1 + minusMonth)).getTime() - 86400000));
    }

    formatToday(firstOfMonth = false, minusMonth = 0) {
        return AppComponent.formatDate(new Date(), firstOfMonth, minusMonth);
    }
}
