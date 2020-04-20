<template>
  <div>
    <div id="wiki-toolbar" class="row">
      <div class="col-md-12">
        <div class="btn-group" role="group" aria-label="...">
          <button @click="insertAtCaret('**', '**')" type="button" class="btn btn-sm btn-secondary" title="Pogrubienie"><i class="fas fa-bold fa-fw"></i></button>
          <button @click="insertAtCaret('*', '*')" type="button" class="btn btn-sm btn-secondary" title="Kursywa"><i class="fas fa-italic fa-fw"></i></button>
          <button @click="insertAtCaret('<u>', '</u>')" type="button" class="btn btn-sm btn-secondary" title="Przekreślenie"><i class="fas fa-underline fa-fw"></i></button>
          <button @click="insertAtCaret('`', '`')" type="button" class="btn btn-sm btn-secondary" title="Instrukcja kodu"><i class="fas fa-text-width fa-fw"></i></button>
        </div>

        <div class="btn-group" role="group">
          <button @click="insertAtCaret('<br>', '## ')" type="button" class="btn btn-sm btn-secondary" data-open="<br>" data-close="## " title="Nagłówek h2"><i class="fas fa-heading fa-fw"></i></button>
        </div>

        <div class="btn-group" role="group">
          <button @click="insertAtCaret('<br>', '*')" type="button" class="btn btn-sm btn-secondary" title="Wypunktowanie"><i class="fas fa-list-ul fa-fw"></i></button>
          <button @click="insertAtCaret('<br>', '1. ')" type="button" class="btn btn-sm btn-secondary" title="Numerowanie"><i class="fas fa-list-ol fa-fw"></i></button>
          <button @click="insertAtCaret('![title', '](http://)')" type="button" class="btn btn-sm btn-secondary" title="Obraz"><i class="fas fa-image fa-fw"></i></button>
          <button @click="insertAtCaret('[', '](http://)')" type="button" class="btn btn-sm btn-secondary" title="Link"><i class="fas fa-link fa-fw"></i></button>
          <button @click="insertAtCaret('<br>Nagłówek 1 | Nagłówek 2<br>---------------- | -------------------<br>Kolumna1 | Kolumna 2<br>', '')" type="button" class="btn btn-sm btn-secondary" title="Tabela"><i class="fas fa-table fa-fw"></i></button>
          <button @click="insertCitation" type="button" class="btn btn-sm btn-secondary btn-quote" title="Wstaw cytat"><i class="fas fa-quote-left fa-fw"></i></button>

          <div id="select-menu" class="btn-group" :class="{'open': isDropdownShown}">
            <button @click="insertAtCaret('```<br>', '<br>```')" type="button" class="btn btn-sm btn-secondary" title="Kod źródłowy"><i class="fas fa-code fa-fw"></i></button>
            <button @click="toggleDropdown" type="button" class="btn btn-sm btn-secondary dropdown-toggle" aria-haspopup="true" aria-expanded="false">
              <span class="caret"></span>
            </button>

            <div v-if="isDropdownShown" v-on-clickaway="hideDropdown" class="dropdown-menu select-menu">
              <div class="select-menu-search">
                <input v-model="searchText" @keyup.esc="hideDropdown" ref="search" type="text" class="form-control form-control-sm" placeholder="Filtruj..." autocomplete="off">
              </div>
              <div class="select-menu-wrapper" ref="langScrollableContent">
                <ul class="list-unstyled">
                  <li v-for="(value, key) in filteredValue" ref="langNode">
                    <a @click="insertAtCaret('```' + key + '<br>', '<br>```<br>')" @mouseenter="clearCurrentLang" v-bind:class="[activeLang === key ? 'active' : '']">{{ value }}</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-sm btn-secondary float-right" data-toggle="collapse" data-target="#wiki-help" aria-expanded="false" aria-controls="wiki-help"><i class="fas fa-question fa-fw"></i></button>
      </div>
    </div>

    <div id="wiki-help" class="row collapse">
      <div class="col-md-12">
        <div class="card card-info">
          <div class="card-header">Pomoc</div>
          <div class="card-body">
            <h2>Pogrubienie, kursywa...</h2>

            <p>Możesz używać pogrubienia czy kursywy, aby usprawnić czytelność tekstu: <code>**to jest pogrubienie**</code>, a to
              <code>*kursywa*</code>.
            </p>

            <h2>Kod źródłowy</h2>

            <p>Wszelkie jednolinijkowe instrukcje języka programowania (fragmenty kodu) powinny być zawarte pomiędzy obrócone
              apostrofy
              lub podwójny cudzysłów, czyli: <code>`kod instrukcji języka programowania`</code>.</p>

            <p><code>```</code> umożliwia kolorowanie większych fragmentów kodu. Możemy nadać nazwę języka
              programowania,
              aby system użył konkretnych ustawień kolorowania składnii:
              <br/><br/>
              <code>
                ```javascript<br/>
                &nbsp;&nbsp;document.write('Hello World');<br/>
                ```<br/>
              </code></p>

            <h2>Nagłówki</h2>

            <p>
              <code>## Nagłówek 2</code><br/>
              <code>### Nagłówek 3</code><br/>
              <code>#### Nagłówek 4</code>
            </p>

            <h2>Wypunktowanie i numerowanie</h2>

            <p>Możliwe jest tworzenie listy numerowanych oraz wypunktowanych. Wystarczy, że pierwszym znakiem linii będzie
              <code>*</code> lub <code>1. </code></p>

            <p>
              <code>1. Lista numerowana</code><br/>
              <code>2. Lista numerowana</code><br/>
            </p>
            <p></p>
            <p>
              <code>* Lista wypunktowana</code><br/>
              <code>* Lista wypunktowana</code><br/>
              <code>** Lista wypunktowana (drugi poziom)</code><br/>
            </p>

            <h2>Linki</h2>

            <p>URL umieszczony w tekście zostanie przez system automatycznie wykryty i zamieniony na znacznik <code>
              &lt;a&gt;</code>.<br/>
              Jeżeli chcesz, możesz samodzielnie sformatować link: <code>&lt;a href="http://4programmers.net">kliknij tutaj&lt;/a&gt;</code>
            </p>

            <p>Możesz umieścić odnośnik do wewnętrznej podstrony, używając następującej składnii: <code>[[Delphi/Kompendium]]</code>
              lub <code>[[Delphi/Kompendium|kliknij, aby przejść do kompendium]]</code></p>

            <h2>Znaczniki HTML</h2>

            <p>Dozwolone jest używanie podstawowych znaczników HTML: &lt;a&gt;, &lt;b&gt;, &lt;i&gt;, &lt;del&gt;, &lt;strong&gt;,
              &lt;tt&gt;, &lt;dfn&gt;, &lt;ins&gt;, &lt;pre&gt;, &lt;blockquote&gt;, &lt;hr&gt;, &lt;sub&gt;, &lt;sup&gt;, &lt;img&gt;</p>

            <h2>Indeks górny oraz dolny</h2>

            <p>Przykład: wpisując <code>m&lt;sub&gt;2&lt;/sub&gt;,, i m&lt;sup&gt;2&lt;/sup&gt;</code> otrzymasz: m<sub>2</sub> i m<sup>2</sup>.</p>

            <h2>Składnia Tex</h2>

            <p><code>&lt;tex&gt;arcctg(x) = argtan(\frac{1}{x}) = arcsin(\frac{1}{\sqrt{1+x^2}})&lt;/tex&gt;</code></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { default as Textarea, languages } from '../../libs/textarea';
  import { mixin as clickaway } from 'vue-clickaway';

  export default {
    mixins: [clickaway],
    props: {
      input: {
          type: Function
      }
    },
    data() {
      return {
        textarea: null,
        languages,
        searchText: '',
        isDropdownShown: false,
        currentLang: undefined
      }
    },
    mounted() {
      this.textarea = new Textarea(this.input());
    },
    created() {
      document.addEventListener('keydown', this.langNavigator);
    },
    destroyed() {
      document.removeEventListener('keydown', this.langNavigator);
    },
    methods: {
      insertAtCaret(startsWith, endsWith) {
        this.textarea.insertAtCaret(startsWith.replace(/<br>/g, "\n"), endsWith.replace(/<br>/g, "\n"), this.textarea.isSelected() ? this.textarea.getSelection() : '');
        this.hideDropdown();

        this.$emit('update', this.textarea.value);
      },

      insertCitation() {
        this.textarea.insertAtCaret('> ', '', this.textarea.getSelection().replace(/\r\n/g, "\n").replace(/\n/g, "\n> "));

        this.$emit('update', this.textarea.value);
      },

      toggleDropdown() {
        this.isDropdownShown = !this.isDropdownShown;

        if (this.isDropdownShown) {
          this.$nextTick(() => this.$refs.search.focus());
        }
      },

      hideDropdown() {
        this.isDropdownShown = false;
        this.currentLang = undefined;
      },

      clearCurrentLang() {
        this.currentLang = undefined;
      },

      langNavigator(e) {
        if (this.isDropdownShown) {
          switch (e.keyCode) {
            case 13: {
              this.insertActiveLang(e);
            }break;
            case 38: {
              this.markPrevLangActive();
            }break;
            case 40: {
              this.markNextLangActive();
            }break;
          }
        }
      },

      insertActiveLang(e) {
        if (this.currentLang) {
          e.preventDefault();
          this.insertAtCaret('```' + this.currentLang + '<br>', '<br>```<br>');
          this.hideDropdown();
        }
      },

      activeLangIndex() {
        return Object.keys(this.filteredValue).indexOf(this.activeLang)
      },

      markNextLangActive() {
        let activeLangIndex = this.activeLangIndex();

        if (activeLangIndex < Object.keys(this.filteredValue).length - 1) {
          activeLangIndex++;
        } else {
          activeLangIndex = 0;
        }

        this.currentLang = Object.keys(this.filteredValue)[activeLangIndex];

        this.scrollToActiveLang();
      },

      markPrevLangActive() {
        let activeLangIndex = this.activeLangIndex();

        if (activeLangIndex > 0) {
          activeLangIndex--;
        } else {
          activeLangIndex = Object.keys(this.filteredValue).length - 1;
        }

        this.currentLang = Object.keys(this.filteredValue)[activeLangIndex];

        this.scrollToActiveLang();
      },

      scrollToActiveLang() {
        const activeLangIndex = this.activeLangIndex();

        this.$refs.langScrollableContent.scrollTop = activeLangIndex * this.$refs.langNode[0].offsetHeight;
      }
    },
    computed: {
      activeLang() {
        return this.currentLang;
      },
      filteredValue() {
        this.currentLang = undefined;

        return Object
          .keys(this.languages)
          .filter(language => language.toLowerCase().startsWith(this.searchText))
          .reduce((obj, key) => {
            obj[key] = this.languages[key];

            return obj;
          }, {});
      }
    }
  }
</script>
