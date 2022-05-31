import { useCallback, useMemo, useRef } from "react";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";
import { Line } from "react-chartjs-2";
import "./Graphs.css";
import Axios from "axios";
import { useState, useEffect } from "react";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const options = {
  lineTension: 0,
  fill: false,
  showLines: false,
  pointRadius: 1,
  pointHoverRadius: 1,
  legend: {
    labels: {
      boxHeight: 10,
      boxWidth: 50,
    },
  },
  scales: {
    xAxes: [
      {
        ticks: {
          autoSkip: false,
          maxRotation: 90,
          minRotation: 90,
        },
      },
    ],
  },
};

export default function LineChart() {
  const [scores, setScores] = useState([]);
  const [labels, setLabels] = useState([]);
  const [labelWithDays, setLabelWithDays] = useState([]);
  const [labelOnlyMonths, setLabelOnlyMonths] = useState([]);
  const [isLong, setIsLong] = useState(false);
  useEffect(() => {
    getAllPlayers();
  }, []);
  const getAllPlayers = async () => {
    try {
      const config = {
        params: {
          id_journal_graph: 431,
          name_row: "f2",
          from: "2019-01-01",
          to: "2020-01-01",
        },
      };
      await Axios.get("http://localhost:8080/api/index.php", config).then(
        (res) => {
          let f1 = [];
          let f2 = [];
          let labels = [];
          res.data.forEach((element) => {
            f1.push(element["f1"]);
            f2.push(element["f2"]);
          });

          f1.forEach((element) => {
            if (!labels.includes(element)) labels.push(element);
          });
          setLabels(labels);
          setScores(f2);
          setLabelWithDays(f1);
          setLabelOnlyMonths(labels);
        }
      );
    } catch (error) {
      console.log(error);
    }
  };
  let ref = useRef(null);
  const downloadImage = useCallback(() => {
    const link = document.createElement("a");
    link.download = "chart.png";
    link.href = ref.current.toBase64Image();
    link.click();
  }, []);

  const data = {
    datasets: [
      {
        label: "показатели режима водохранилища",
        data: scores,
        tension: 0.9,
        borderColor: "rgb(75, 192, 192)",
        pointRadius: 1,
        pointBackgroundColor: "rgb(75, 192, 192)",
        backgroundColor: "rgba(75, 192, 192, 0.3)",
      },
    ],
    labels,
  };

  return (
    <div className="container">
      <button type="button" onClick={downloadImage}>
        Download
      </button>
      <button
        type="button"
        onClick={() => {
          if (isLong) {
            setLabels(labelOnlyMonths);
            setIsLong(false);
          } else {
            setLabels(labelWithDays);
            setIsLong(true);
          }
        }}
      >
        Only months
      </button>
      <Line ref={ref} data={data} options={options} />
    </div>
  );
}
