<!DOCTYPE html>
<html>
<head>
	<title>Graphiques</title>
	<meta charset="utf-8">
	<style type="text/css">
		* {
			display: inline-block;
			padding: 0;
			margin: 0;
		}

		html, body {
			width: 100%;
			height: 100%;
		}
		body {
			display: flex;
			justify-content: center;
			align-items: center;
		}
		script, head {
			display: none;
		}

		body > div {
		    display: flex;
		    position: relative;
		    width: 100%;
		    height: 100%;
		    align-items: center;
		    justify-content: center;
		}

		div > canvas {
		    position: absolute;
		    background-color: transparent;
		}

		.main {
			border-radius: 10px;
			box-shadow: 0 0 10px lightgray;
		}
	</style>
</head>
<body>

	<div id="Graph"></div>

	<script type="text/javascript" src="jquery.js"></script>
	<script>

		var Graph;

		function randomValues(length, max) {

			var tab = [];
			for(var i = 0; i < length; i++) {
				tab.push(Math.round(Math.random() * max));
			}

			return tab;

		}

		(jQuery)(function($) {

			function setGraph(container, settings) {

				// Variable globale contenant l'objet
				var Graph = this;

				let defaults = {
					dots: true,
					fillPaths: false,
					width: 500,
					animation: false,
					height: 500,
					legend: '',
					paths: []
				};

				Graph.settings = $.extend(defaults, settings);

				/*
				*	FONCTIONS
				*/

				function setOrigins(canvas) {
					canvas.ctx.translate(Graph.y.offset, Graph.x.offset);
				}

				function formatLegend(legend) {
					let values = [];
					let display0 = false;
					let nb_values = 0;

					if(Array.isArray(legend)) {
						values = legend;
					}
					else if(Number.isInteger(legend)) {
						nb_values = legend;
					}
					else if(typeof legend === 'string') {
						display0 = true;
						switch(legend) {
							case 'days'  : values = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']; break;
							case 'week'	 : nb_values = 7; break;
							case 'month' : nb_values = 31; break;
							case 'year'	 : values = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']; break;
						}
					}

					if(nb_values > 0) {
						for(var i = 1; i < nb_values ; i++) {
							values.push(( i < 10 && display0 ? '0' : '' ) + i);
						}
					}

					return values;
				}

				Graph.drawAxe = function(axe, index, text, styles, offset) {

					this.styles = styles;
					this.index = index;
					this.axe = axe;
					this.text = text;
					this.offset = typeof offset === "undefined" ? 5 : offset;

					this.init = () => {
						let move_x;
						let move_y;
						let to_x;
						let to_y;

						for(param in this.styles) {
							Graph.ctx[param] = this.styles[param];
						};

						if(this.axe === 'x') {

							move_x = Graph.x.spacing * this.index;
							move_y = 0;

							to_x = move_x;
							to_y = Graph.y.length * -1;

						}
						else {

							move_x = 0;
							move_y = Graph.y.spacing * this.index * -1;

							to_x = Graph.x.length;
							to_y = move_y;

						}

						this.x1 = move_x;
						this.y1 = move_y;

						this.x2 = to_x;
						this.y2 = to_y;

						this.draw();
					};

					this.draw = () => {
						Graph.ctx.beginPath();
						Graph.ctx.moveTo(this.x1, this.y1);

						if(this.text !== '') {
							let textPxLength = Graph.ctx.measureText(text);
							let set_x;
							let set_y;
							let text_offset = this.index === 0 ? 0 : Graph[this.axe].spacing * this.index;

							if(this.axe === 'x') {
								set_x = text_offset - ( textPxLength.width / 2 );
								set_y = ( Graph.canvas_height -  Graph.x.offset ) / 2;
							}
							else if(this.axe === 'y') {
								set_x = ( Graph.y.offset / 2 + ( textPxLength.width / 2 ) ) * -1;
								set_y = ( text_offset - ( parseInt(Graph.ctx.font) / 2 ) ) * -1;
							}

							Graph.ctx.fillText(text, set_x, set_y);
						}

						// On trace
						Graph.ctx.lineTo(this.x2, this.y2);
						Graph.ctx.stroke();
						Graph.ctx.closePath();
					};

					this.update = (speed, y1) => {
						this.y1 = y1;
						this.y2 += speed;

						this.draw();
					};

					this.init();

				};

				Graph.setLegend = (axe) => {

					// Paramètres des lignes des axes
					let settings = {
						lineWidth: .5,
						strokeStyle: 'lightgrey',
						font: "10pt sans-serif",
						fillStyle: "black"
					};

					if(axe === 'x') {
						Graph.ctx.clearRect(0, 20, Graph.canvas_width, Graph.canvas_height);

						for(var i = 1; i < Graph.x.legend.length + 1; i++) {
							// Texte de la légende
							var texte = Graph.x.legend[i - 1];

							// On dessine les axes X
							new Graph.drawAxe('x', i, texte, settings);
						}
					}
					else {
						Graph.ctx.clearRect(-5, 10, Graph.canvas_width * -1, Graph.canvas_height * -1);

						let maxValue = 0;
						let minValue = 0;

						for(key in Graph.settings.paths) {
							let values = Graph.updating ? Graph.updatingValues : Graph.settings.paths[key].values;
							for(index in values) {
								maxValue = Math.max(maxValue, Math.round(values[index]));
								minValue = Math.min(minValue, Math.round(values[index]));
							}
						}

						if(maxValue % 10 !== 0 && maxValue % 5 !== 0) {
							let sizeMax = String(maxValue).length - 2;

							if(sizeMax <= 0)
								sizeMax = 1;

							let moduloMax = '1';

							for(let i = 0; i < sizeMax; i++) {
								moduloMax += '0';
							}

							while(maxValue % parseInt(moduloMax) !== 0) {
								maxValue++;
							}
						}

						if(minValue % 10 !== 0 && minValue % 5 !== 0) {
							let sizeMin = String(minValue).length - 2;

							if(sizeMin <= 0)
								sizeMin = 1;

							let moduloMin = '1';

							for(let i = 0; i < sizeMin; i++) {
								moduloMin += '0';
							}

							while(minValue % parseInt(moduloMin) !== 0) {
								minValue--;
							}
						}

						Graph.y.maxValue = maxValue;
						Graph.y.spacing = Graph.y.length / maxValue;

						let iterator = 1;
						if(maxValue >= 10 && maxValue < 100) {
							iterator = 5;
						}
						else if(maxValue >= 100){
							iterator = maxValue / 10;
						}

						for(var i = 0; i <= maxValue; i += iterator) {
							// Texte de la légende
							var texte = i;
							// On définit la légende de Y
							Graph.y.legend.push(texte);
							// On dessine les axes Y
							new Graph.drawAxe('y', ( i / iterator ) * iterator, texte, settings);
						}
					}

				};

				// Dessin des axes
				Graph.setAxes = function() {

					// On clear toute la surface
					Graph.ctx.clearRect(Graph.canvas_width * -1, Graph.canvas_height, Graph.canvas_width * 2, Graph.canvas_height * -1 * 2);

					// Origine
					new Graph.drawAxe('y', 0, '0', { font: '10pt sans-serif', fillStyle: 'black' });

					// Tracé de l'axe X
					Graph.setAxe('x');

					// Tracé de l'axe Y
					Graph.setAxe('y');

				};

				// Dessins d'un axe
				Graph.setAxe = function(axe) {

					Graph.ctx.beginPath();
					Graph.ctx.lineWidth = 2;
					Graph.ctx.strokeStyle = "black";

					// L'origine du graphique étant l'intersection des origines
					Graph.ctx.moveTo(0, 0);

					if(axe === 'x') {
						Graph.ctx.lineTo(Graph.x.length, 0);
					}
					else {
						Graph.ctx.lineTo(0, Graph.y.length * -1);
					}

					// Tracé des lignes des origines
					Graph.ctx.stroke();

					// On ferme le path
					Graph.ctx.closePath();

					// On incorpore la légende
					Graph.setLegend(axe);

				}

				Graph.setPath = function(index, path) {

					var Path = this;
					Path.values = path.values;

					Path.init = (values) => {

						let width = 1;
						if(typeof Graph.settings.pathWidth !== "undefined")
							width = Graph.settings.pathWidth;
						if(typeof path.width !== "undefined")
							width = path.width;

						let defaults = {
							color: 'black',
							width: width
						};

						Path.index = index;
						Path.settings = $.extend(defaults, path);

						// Context du canvas
						Path.ctx = Graph.paths[Path.index].ctx;

						/*
						*  ON DESSINE LES TRACES
						*/

						Path.Lines(values);

						// On remplit le tracé
						if(Graph.settings.fillPaths)
							Path.Lines(values, true);

						/*
						*  ON DESSINE LES POINTS
						*/

						if(Graph.settings.dots && !Graph.updating) {

							// On trace les lignes successivement
							for(j in values) {

								let value = values[j];
								let key = parseInt(j) + 1;

								let x = Graph.x.spacing * key;
								let y = value * ( Graph.updating ? 1 : Graph.y.spacing ) * -1;

								if(Graph.settings.animation && Graph.updating === false) {
									y = 0;
								}

								var dot = new Path.Dot(x, y, Path.settings.width + 3, Path.settings.color);
								if(!Graph.updating)
									Graph.paths[Path.index].dots[j] = dot;
							}

						}
					}

					Path.Lines = function(values, fill = false) {
						// On commence la tracé
						Path.ctx.beginPath();
						Path.ctx.globalAlpha = 1;
						Path.ctx.strokeStyle = Path.settings.color;
						Path.ctx.lineWidth 	 = Path.settings.width;

						if(fill) {
							Path.ctx.lineWidth 	 = 0;
							Path.ctx.strokeStyle = "transparent";
							Path.ctx.globalAlpha = .1;
							Path.ctx.fillStyle 	 = Path.settings.color;
						}

						// On commence à l'origine
						Path.ctx.moveTo(0,0);

						// On trace les lignes successivement
						for(i in values) {

							var line;

							let key = parseInt(i) + 1;
							let value = values[i];
							let prevValue = values[key - 1];

							let x1 = Graph.x.spacing * key;
							let y1 = Math.abs(value) * ( Graph.updating ? 1 : Graph.y.spacing ) * -1;

							let x2 = key === 1 ? 0 : Graph.x.spacing * ( key - 1 );
							let y2 = key === 1 ? 0 : Math.abs(prevValue) * ( Graph.updating ? 1 : Graph.y.spacing ) * -1;

							if(Graph.settings.animation && Graph.updating === false) {
								y1 = 0;
								y2 = 0;
							}

							if(fill)
								Path.ctx.lineTo(x1, y1);
							else
								line = new Path.Line(x1, y1, x2, y2);

							if(!Graph.updating && !fill)
								Graph.paths[Path.index].lines[i] = line;
						}

						// On dessine le dernier tracé pour << fermer >> le graphique
						Path.ctx.lineTo(Graph.x.length, 0);

						// On rempli le tracé si voulu
						if(fill)
							Path.ctx.fill();

						// On dessine et ferme le tracé
						Path.ctx.stroke();
						Path.ctx.closePath();

					}

					Path.Line = function(x1, y1, x2, y2) {

						this.x1 = x1;
						this.y1 = y1;

						this.x2 = x2;
						this.y2 = y2;

						this.draw = () => {
							Path.ctx.lineTo(this.x1, this.y1);
						};

						this.update = (speed, sens) => {
							this.y1 += speed * sens;
							this.draw();
						}

						this.draw();
					}

					Path.Dot = function(x, y, size, fillColor) {

						this.x = x;
						this.y = y;
						this.size = size;
						this.fillColor = fillColor;

						this.init = () => {
							this.draw();
						};

						this.draw = () => {
							Path.ctx.beginPath();
							Path.ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
							Path.ctx.globalAlpha = 1;
							Path.ctx.fillStyle = this.fillColor;
							Path.ctx.fill();
							Path.ctx.closePath();
						};

						this.update = (value) => {
							this.y = value;
							this.draw();
						};

						this.init();

					}

					Path.init(Path.values);
				}

				Graph.updatePath = (noPath, values, speed) => {

					if(Graph.updating)
						return;

					if(values.length === 0)
						values = randomValues(7, 50);

					var loop = 0;
					var path 		= Graph.paths[parseInt(noPath) + 1];
					var remaining 	= [];
					var diff 		= [];
					var cumulDiff 	= [];
					var nextValue 	= [];
					var speedTab	= [];

					function transition() {

						path.ctx.clearRect(-5,5, Graph.canvas_width, Graph.canvas_height * -1);

						values.forEach(function(pathTo, index) {

							pathTo = pathTo * Graph.y.spacing;
							var value = Graph.values[noPath][index] * Graph.y.spacing;
							var comparator = pathTo < value ? 1 : ( pathTo > value ? -1 : 0 );

							if(typeof diff[index] === "undefined")
								diff[index] = Math.abs(Math.abs(pathTo) - Math.abs(value)) / 2;
							if(typeof cumulDiff[index] === "undefined")
								cumulDiff[index] = 0;
							if(typeof nextValue[index] === "undefined")
								nextValue[index] = value;
							if(typeof speedTab[index] === "undefined")
								speedTab[index] = speed;

							var traveled = Math.abs(comparator < 0 ? value - Math.abs(nextValue[index]) : Math.abs( nextValue[index]) - pathTo);
							var valY = traveled - ( comparator < 0 ? cumulDiff[index] : 0 );
							if(( comparator < 0 && valY > diff[index]) || ( comparator > 0 && valY < diff[index] )) {
								if(comparator < 0)
									cumulDiff[index] += diff[index];
								diff[index] = diff[index] / 2;
								if(comparator > 0)
									cumulDiff[index] = Math.abs(cumulDiff[index] - diff[index]);
								speedTab[index] = speedTab[index] / 2;
							}

							if(diff[index] <= .1)
								diff[index] = 0;

							if(diff[index] > .1 && value !== pathTo) {
								nextValue[index] = nextValue[index] + speedTab[index] * comparator * -1;
								if(( nextValue[index] > pathTo && comparator < 0) || ( nextValue[index] < pathTo && comparator > 0)) {
									nextValue[index] = pathTo;
								}
							}
							else {
								if($.inArray(index, remaining) === -1)
									remaining.push(index);

								nextValue[index] = comparator === 0 ? pathTo : pathTo * comparator;
							}

							path.dots[index].update(Math.abs(nextValue[index]) * -1);

						});

						path.setter.init(nextValue);

						if(remaining.length < path.dots.length)
							requestAnimationFrame(transition);
						else {
							Graph.updating = false;
							Graph.values[noPath] = values;
						}

						loop++;
					}

					// var nullValues = [];
					// for(var i = 0, i < values.length; i++) {
					// 	nullValues.push(0);
					// }
					// Graph.updatePath(noPath, nullValues, speed);

					Graph.updating = true;
					Graph.updatingValues = values;

					// On redessine les axes
					Graph.setAxes();

					transition();
				}

				Graph.newCanvas = function() {

					let count = typeof Graph.numberOfCanvas === "undefined" ? 0 : Graph.numberOfCanvas;

					let NewCanvas = document.createElement('canvas');
					NewCanvas.width = Graph.settings.width;
					NewCanvas.height = Graph.settings.height;
					NewCanvas.classList = [name];

					Graph.container.appendChild(NewCanvas);

					if(typeof Graph.paths === "undefined")
						Graph.paths = [];

					// On ajoute le nouveau canvas à la liste
					var handlePath = {
						container: NewCanvas,
						ctx: NewCanvas.getContext('2d'),
						lines: [],
						dots: []
					};
					Graph.paths.push(handlePath);

					Graph.numberOfCanvas = count + 1;

					if(count > 0)
						setOrigins(Graph.paths[count]);
				}

				/* INITIALISATION */

				Graph.container = document.getElementById(container);

				Graph.newCanvas();
				Graph.ctx    = Graph.paths[0].ctx;
				Graph.updating = false;

				Graph.canvas_height = Graph.paths[0].container.height;
				Graph.canvas_width  = Graph.paths[0].container.width;

				Graph.x_offset = 90;
				Graph.y_offset = 8;

				// Définition des données des axes
				Graph.x = {
					offset: Graph.canvas_height * Graph.x_offset / 100,
					legend: formatLegend(Graph.settings.legend)
				};
				Graph.y = {
					offset: Graph.canvas_width * Graph.y_offset / 100,
					legend: []
				};

				// Longueur des axes
				Graph.x.length = Graph.canvas_width - Graph.y.offset * 1.5
				Graph.y.length = Graph.x.offset - ( Graph.canvas_height - Graph.x.offset );

				// Espacement des lignes des axes
				Graph.x.spacing = Graph.x.length / ( Graph.x.legend.length + 1 );

				// On définit l'origine 0,0 à la jonction des origines
				setOrigins(Graph.paths[0]);

				// On dessine les axes
				Graph.setAxes();

				Graph.values = [];

				// On dessine les tracés
				for(index in Graph.settings.paths) {
					let path = Graph.settings.paths[index];

					Graph.values.push(path.values);
					Graph.newCanvas();

					if(Graph.settings.animation) {
						var nullValues = [];
						for(var i = 0; i < values.length; i++) {
							nullValues.push(0);
						}
						path.values = nullValues;
					}
					console.log(path.values);
					Graph.paths[parseInt(index) + 1].setter = new Graph.setPath(parseInt(index) + 1, path);

					if(Graph.settings.animation) {
						setTimeout(function() {
							Graph.updatePath(index, path.values, 20);
						}, 500);
					}
				}

				// Graph.newCanvas();
				// var value = 25;
				// var pathTo = 5;
				// var diff = Math.abs(Math.abs(pathTo) - Math.abs(value)) / 2;
				// var cumulDiff = 0;
				// var nextValue = value;
				// var speed = 2;
				// var loop = 0;
				// Graph.paths[1].ctx.beginPath();
				// Graph.paths[1].ctx.fillStyle = 'black';
				// Graph.paths[1].ctx.arc(Graph.x.spacing * 1, Graph.y.spacing * value * -1, 5, 0, Math.PI * 2, false);
				// Graph.paths[1].ctx.fill();
				// Graph.paths[1].ctx.closePath();

				// function animate() {

				// 	Graph.paths[1].ctx.clearRect(-5,5,1000, -1000);

				// 	var comparator = pathTo < value ? 1 : -1;
				// 	var traveled = Math.abs(comparator < 0 ? value - Math.abs(nextValue) : nextValue - pathTo);
				// 	var valY = traveled - ( comparator < 0 ? cumulDiff : 0 );
				// 	if(( comparator < 0 && valY > diff) || ( comparator > 0 && valY < diff )) {
				// 		if(comparator < 0)
				// 			cumulDiff += Math.abs(diff);
				// 		diff = Math.abs(diff) / 2;
				// 		if(comparator > 0)
				// 			cumulDiff = Math.abs(cumulDiff - diff);
				// 		speed = speed / 2;
				// 	}
				// 	console.log('VALUE:'+Math.abs(nextValue));
				// 	console.log('TO:'+valY);
				// 	console.log('STATE:'+cumulDiff);
				// 	console.log('DIFF:'+diff);
				// 	console.log('##');

				// 	if(diff > .1)
				// 		nextValue = nextValue + speed * comparator * -1;
				// 	else
				// 		nextValue = pathTo * comparator;
				// 	// console.log(Graph.y.spacing * nextValue);
				// 	Graph.paths[1].ctx.beginPath();
				// 	Graph.paths[1].ctx.fillStyle = 'black';
				// 	Graph.paths[1].ctx.arc(Graph.x.spacing * 5, Math.abs(Graph.y.spacing * nextValue) * -1, 5, 0, Math.PI * 2, false);
				// 	Graph.paths[1].ctx.fill();
				// 	Graph.paths[1].ctx.closePath();

				// 	if(diff > .1) {
				// 		requestAnimationFrame(animate);
				// 	}

				// 	loop++;

				// }

				// animate();

				return Graph;

			}

			var values = randomValues(7, 50);

			let settings = {
				width: 1000,
				height: 500,
				// legend: "days",
				// animation: true,
				legend: "days",
				pathWidth: 2,
				dots: true,
				fillPaths: true,
				paths: [
					{
						color: '#ff803c',
						values: values
						// values: [0, 5, 10, 15, 20, 25, 30]
					},
					// {
					// 	color: 'grey',
					// 	values: [8,1,5,13,8,2,5]
					// 	// values: values2
					// }
				]
			};

			Graph = new setGraph("Graph", settings);

		});


	</script>
</body>
</html>