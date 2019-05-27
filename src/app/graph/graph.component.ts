import {Component, OnInit} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {ChartDataSets, ChartOptions} from 'chart.js';
import {AppComponent} from '../app.component';
import {GLOBAL} from '../GLOBAL';
import {Label} from 'ng2-charts';
import * as pluginAnnotations from 'chartjs-plugin-annotation';

@Component({
    selector: 'app-graph',
    templateUrl: './graph.component.html',
    styleUrls: ['./graph.component.scss']
})
export class GraphComponent implements OnInit {

    constructor(private http: HttpClient,
                private route: ActivatedRoute) {
    }

    private static oneDayInMillis = 86400000;

    public loggedIn: string = localStorage.getItem(AppComponent.loggedInKey);

    public loading = true;
    public queueIndex;
    public lineChartData: ChartDataSets[];
    public lineChartLabels: Label[];
    public lineChartPlugins = [pluginAnnotations];
    public lineChartOptions: (ChartOptions & { annotation: any }) = {
        responsive: true,
        aspectRatio: 3,
        annotation: {
            annotations: [{
                type: 'line',
                mode: 'vertical',
                scaleID: 'x-axis-0',
                value: AppComponent.formatDate(),
                borderColor: 'orange',
                borderWidth: 2,
                label: {
                    enabled: true,
                    fontColor: 'orange',
                    content: 'Today'
                }
            }],
        }
    };

    public ticketIds = [];
    public ticketQueue = {};
    public minDate;
    public maxDate;
    public scopeDate;

    private static loopThroughDates(call: (date: string) => void, startDate = '2000-01-01', endDate: null | Date = null) {
        let lastDate = startDate;
        let lastDateObj = new Date(lastDate);
        let today;
        if (endDate == null) {
            today = AppComponent.formatDate();
        } else {
            today = AppComponent.formatDate(endDate);
        }
        let iteration = 100000;
        call(lastDate);
        while (lastDate < today && (iteration-- > 0)) {
            lastDateObj = new Date(new Date(lastDate).getTime() + this.oneDayInMillis);
            lastDate = AppComponent.formatDate(lastDateObj);

            call(lastDate);
        }
    }

    ngOnInit() {
        this.route.queryParams.subscribe(param => {
            this.resetData();
            if (param.min) {
                this.minDate = param.min;
                this.scopeDate = param.min;
            }
            if (param.max) {
                this.maxDate = new Date(param.max);
            }
            this.loadQueues();
        });
    }

    resetData() {
        this.minDate = AppComponent.formatDate();
        this.maxDate = new Date(new Date(AppComponent.formatDate(null, true, -1)).getTime() - GraphComponent.oneDayInMillis);
        this.scopeDate = AppComponent.formatDate(new Date(), true);
        this.queueIndex = [];
        this.lineChartData = [];
        this.lineChartLabels = [];
        this.ticketIds = [];
        this.ticketQueue = {};
        this.loading = true;
    }

    loadQueues() {
        const headers = new HttpHeaders().set('x-dell-api-version', '1').set('x-dell-csrf-token', this.loggedIn);

        this.http.get(GLOBAL.kaceURL + 'api/service_desk/queues/', {headers, withCredentials: true}).subscribe((response: any) => {
            response.Queues.forEach(queue => {
                this.queueIndex.push(queue.id);
                this.lineChartData.push({
                    data: [],
                    label: queue.name,
                });
            });
            this.lineChartData.push({data: [], label: 'All'});
            this.loadTickets(headers);
        });
    }

    loadTickets(headers) {
        this.http.get(GLOBAL.kaceURL + 'api/service_desk/tickets/' +
            '?paging=limit ALL', {headers, withCredentials: true}).subscribe((response: any) => {
            response.Tickets.forEach(ticket => {
                this.ticketIds.push(ticket.id);
                this.ticketQueue[ticket.id] = ticket.hd_queue_id;
                const ticketDate = ticket.created.split(' ')[0];
                if (ticketDate < this.minDate) {
                    this.minDate = ticketDate;
                }
            });
            this.prepareData();
            this.getTicketHistory(headers);
        });
    }

    prepareData() {
        let max = null;
        if (this.maxDate < new Date()) {
            max = this.maxDate;
        }
        GraphComponent.loopThroughDates(date => {
            if (date >= this.scopeDate) {
                this.lineChartLabels.push(date);
                this.lineChartData.forEach(dataSet => {
                    (dataSet.data as number[]).push(0);
                });
            }
        }, this.minDate, max);
        if (max == null) {
            const tomorrow = new Date(new Date().getTime() + GraphComponent.oneDayInMillis);
            GraphComponent.loopThroughDates(date => {
                if (date >= this.scopeDate) {
                    this.lineChartLabels.push(date);
                }
            }, AppComponent.formatDate(tomorrow), this.maxDate);
        }
    }

    async getTicketHistory(headers) {
        for (const id of this.ticketIds) {
            const ticketHistory: any = await this.http.get(GLOBAL.kaceURL + 'api/service_desk/tickets/' + id + '/changes', {
                headers,
                withCredentials: true
            }).toPromise();
            const history = ticketHistory.Changes;
            const regexStatus = /("\w+") to ("\w+")\./;

            const createDate = history[0].timestamp.split(' ')[0];
            let closeDate = null;
            let queueIndex = -1;
            history.forEach(change => {
                if (change.description.indexOf('Changed ticket Status') === 0) {
                    const regexResult = regexStatus.exec(change.description);
                    if (regexResult[2] === '"Closed"' || regexResult[2] === '"Opgelost"') {
                        closeDate = change.timestamp.split(' ')[0];
                    }
                }
                queueIndex = this.queueIndex.indexOf(this.ticketQueue[change.hd_ticket_id]);
            });


            closeDate = closeDate ? new Date(new Date(closeDate).getTime() - GraphComponent.oneDayInMillis) : null;

            GraphComponent.loopThroughDates(date => {
                const index = this.lineChartLabels.indexOf(date);

                (this.lineChartData[queueIndex].data as number[])[index]++;
                (this.lineChartData[this.lineChartData.length - 1].data as number[])[index]++;
            }, createDate, closeDate);
        }
        this.loading = false;
    }
}
