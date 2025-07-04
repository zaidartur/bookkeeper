<script setup>
import { ref, defineProps, onMounted, onBeforeUnmount, computed, shallowRef } from 'vue'
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Panel from 'primevue/panel';
import Badge from 'primevue/badge';
import OverlayBadge from 'primevue/overlaybadge';
import Tag from 'primevue/tag';
import Popover from 'primevue/popover';
import InputMask from 'primevue/inputmask';
import IftaLabel from 'primevue/iftalabel';
import Fieldset from 'primevue/fieldset';
import * as echarts from 'echarts';
import { list } from 'postcss';
import { useLayout } from '@/Layouts/composables/layout';

const datas = defineProps({
    routers: Object,
})

const { layoutConfig } = useLayout()
const lists = ref(Array())
const ethernet = ref(null)
const chartData = ref()
const chartRefs = shallowRef([])
const chartInstances = shallowRef([])
const options = shallowRef([])
const isDarkMode = computed(() => {
    return layoutConfig.darkTheme
})

const initData = () => {
    lists.value = []
    options.value = []
    const parsing = JSON.parse(datas.routers)
    if (parsing.length > 0) {
        parsing.map((ls) => {
            lists.value.push(ls)
            const init = initOption()
            options.value.push(init)

            if (ls) {
                setInterval(() => {
                    setUpdate(ls.id)
                }, 8000)
            }
        })
    }
}

onMounted(() => {
    initCharts()
    lists.value.forEach((ls) => {
        if (ls) {
            setUpdate(ls.id)
        }
    })
    // console.log('lists', lists.value)
})

onBeforeUnmount(() => {
    disposeCharts()
});


let dataTx = []
let dataRx = []

const update_graph = (id) => {
    axios.post('/network/graphic', {id: id}).then((response) => {
        // console.log(res.data)
        const res = response.data
        if (res) {
            // dataChart.value.datasets[0].data = [20]
            // setChartData().labels.push(res.time)
            console.log(res)
            // chartData.value.labels.push(res.time)
            const data = chartData.value.datasets
            const sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
            data.forEach((dt, d) => {
                if (dt.label === 'Rx') {
                    const bytes = parseInt(res.rx)
                    if (bytes == 0) return '0 bps';
                    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                    const rx = parseFloat((bytes / Math.pow(1024, i)).toFixed(2))
                    // chartData.value.datasets[d].data.push(rx)
                    dataRx.push(3)
                    const ar = chartData.value.datasets[d].data
                    console.log('rx[]', ar)
                    // console.log('rx', chartData.value.datasets[d].data[0])
                } else if (dt.label === 'Tx') {
                    const bytes = parseInt(res.tx)
                    if (bytes == 0) return '0 bps';
                    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                    const tx = parseFloat((bytes / Math.pow(1024, i)).toFixed(2))
                    dataTx = dataTx ? (dataTx + ',' + tx) : (tx)
                    // chartData.value.datasets[d].data.push(tx)
                    // console.log('tx', chartData.value.datasets[d].data[0])
                }
            })
            
            chartData.value = setChartData();
            // chartOptions.value = setChartOptions();
        }
    })
}

const test = () => {
    //
}

const setUpdate = (id) => {
    if (id > -1) {
        // console.log(id)
        axios.post('/network/graphic', {id: id}).then((response) => {
            const res = response.data
            if (res) {
                // console.log(res)
                ethernet.value = res.name
                updateInterval(res.time, res.rx, res.tx)
            }
        })
    }
}

const updateInterval = (label, rx, tx) => {
    // console.log('updating...')
    updateChart(label, rx, tx)
    chartInstances.value.forEach((instance, index) => {
        if (instance) {
        const { animationDuration, animationEasing, ...rest } = options.value[index];
        instance.setOption({
            ...rest,
            animation: true
        }, true);
        }
    });
    window.dispatchEvent(new Event('resize')); // Trigger resize to handle container changes
}


const colors = ['#5470C6', '#EE6666'];

const initOption = () => {
    return {
        color: colors,
        updates: 0,
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            }
        },
        legend: {},
        grid: {
            top: 70,
            bottom: 50
        },
        xAxis: {
            type: 'category',
            axisTick: {
                alignWithLabel: true
            },
            axisLine: {
                onZero: false,
            },
            axisPointer: {
                label: {
                    formatter: function (params) {
                        return (
                            'Waktu : ' +
                            params.value
                        );
                    }
                }
            },
            splitLine: {
                show: false
            },

            // prettier-ignore
            // data: ['2016-1', '2016-2', '2016-3', '2016-4', '2016-5', '2016-6', '2016-7', '2016-8', '2016-9']
            data: []
        },
        yAxis: [
            {
                type: 'value',
                // boundaryGap: [0, '100%'],
                splitLine: {
                    show: false
                },
                // labels: {
                //     formatter: function () {      
                //         var bytes = this.value;                          
                //         var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
                //         if (bytes == 0) return '0 bps';
                //         var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                //         return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];                    
                //     },
                // },   
            }
        ],
        series: [
            {
                name: 'Tx',
                type: 'line',
                // stack: 'Total',
                smooth: true,
                emphasis: {
                    focus: 'series'
                },
                // areaStyle: {},
                // data: [
                //     2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7
                // ]
                data: []
            },
            {
                name: 'Rx',
                type: 'line',
                // stack: 'Total',
                smooth: true,
                emphasis: {
                    focus: 'series'
                },
                // areaStyle: {},
                // data: [
                //     3.9, 5.9, 11.1, 18.7, 48.3, 69.2, 231.6, 46.6, 55.4
                // ]
                data: []
            }
        ]
    }
}

initData()
const setChartRef = (el, index) => {
    // console.log(index, chartRefs.value)
    chartRefs.value[index] = el
};

const initCharts = () => {
    chartInstances.value = options.value.map((chart, index) => {
        if (chartRefs.value[index]) {
            const instance = echarts.init(chartRefs.value[index]);
            instance.setOption(chart);
            return instance;
        }
        return null;
    });
    window.addEventListener('resize', resizeCharts);
};
const resizeCharts = () => {
    chartInstances.value.forEach(instance => {
        if (instance) instance.resize();
    });
};
const disposeCharts = () => {
    window.removeEventListener('resize', resizeCharts);
    chartInstances.value.forEach(instance => {
        if (instance) instance.dispose();
    });
    chartInstances.value = [];
}

function generateRandomData(count, min, max) {
    return Array.from({ length: count }, () => 
        Math.floor(Math.random() * (max - min + 1)) + min
    );
}

const updateChart = (label, rx, tx) => {
    const maxPoints = 10
    const sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps']
    options.value.forEach(chart => {
        chart.updates++;
        const option = chart;
        // console.log('opt', option)

        if (option.xAxis.data.length >= maxPoints) {
            option.xAxis.data.shift();
            option.series.forEach(series => series.data.shift());
        }

        // Add new time label
        option.xAxis.data.push(label)
        // const now = new Date();
        // option.xAxis.data.push(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        
        // Add new data points
        option.series.forEach(series => {
            const bytes = series.name === 'Tx' ? parseInt(tx) : parseInt(rx)
            let fix = 0
            if (bytes > 0) {
                const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)))
                // return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];  
                fix = parseFloat((bytes / Math.pow(1024, i)).toFixed(2))
            }
            series.data.push(bytes);
        });
    });
}
</script>

<template>
    <Toast />
    <ConfirmDialog></ConfirmDialog>
    <Head title="Network IP" />

    
    <Card class="w-full mb-5">
        <template #title><i class="pi pi-sitemap"></i> Data Network IP {{ isDarkMode }}</template>
    </Card>

    <div class="w-full grid grid-cols-2 justify-between gap-4">
        <Card class="" v-for="(list, l) in lists" :key="l">
            <template #content>
                <Panel toggleable>
                    <template #header>
                        <label class="text-xl" v-if="list && list.name">{{ list.name }}</label>
                        <label class="text-xl" v-if="!list">
                            Router <Badge value="OFF" severity="danger" size="small"></Badge>
                        </label>
                    </template>

                    <div v-if="list" class="flex gap-4">
                        <div class="flex-auto">
                            <label>
                                Status is <Tag severity="success" value="ON" rounded></Tag>
                                <br>
                                Total IP Address : {{ list.data.length }}
                                <br>
                                Total assign network : 0
                            </label>
                        </div>

                        <div>
                            <Button type="button" label="Show IP Network" severity="info" icon="pi pi-eye" raised @click="test" />
                        </div>
                    </div>
                    <div v-if="list" class="w-full">
                        <Divider>Monitoring {{ ethernet }}</Divider>
                        <div class="echart-container" >
                            <div :ref="el => setChartRef(el, l)" class="chart"></div>
                        </div>
                    </div>

                    <div v-if="!list">
                        <label class="">
                            Status is <Tag severity="danger" value="OFF" rounded></Tag>
                            <br>
                            Please turn on your router or API router.
                        </label>
                    </div>
                </Panel>
            </template>
        </Card>
    </div>

    <!-- <Panel class="w-full">
        <div class="echart-container">
            <h1 class="chart-title">Apache ECharts</h1>
            <div ref="myCanvas" class="chart"></div>
        </div>
    </Panel> -->
</template>

<style scoped>
.echart-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background-color: #030712;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 900px;
    margin: 20px auto;
}

.chart {
    width: 100%;
    height: 500px;
    min-height: 300px;
    background-color: white;
    border-radius: 8px;
    padding: 15px;
}
</style>